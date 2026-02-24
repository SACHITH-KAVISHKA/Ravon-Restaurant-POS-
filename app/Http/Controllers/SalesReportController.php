<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSplit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    /**
     * Display the sales report index page with filters.
     */
    public function index(Request $request)
    {
        $query = Order::query();

        // Only completed and paid orders that are not deleted
        $query->where('status', 'completed')
            ->where('is_paid', true)
            ->where('is_deleted', false);

        // Date filters (default to today)
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));
        $orderType = $request->get('order_type');

        // Apply filters
        if ($startDate) {
            $query->whereDate('completed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('completed_at', '<=', $endDate);
        }

        if ($orderType) {
            $query->where('order_type', $orderType);
        }

        // Get paginated orders with relationships
        $orders = $query->with(['payment.splits', 'waiter'])
            ->orderBy('completed_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        // Calculate totals for all filtered orders (not just current page)
        $totalsQuery = Order::query()
            ->where('status', 'completed')
            ->where('is_paid', true)
            ->where('is_deleted', false);

        if ($startDate) {
            $totalsQuery->whereDate('completed_at', '>=', $startDate);
        }

        if ($endDate) {
            $totalsQuery->whereDate('completed_at', '<=', $endDate);
        }

        if ($orderType) {
            $totalsQuery->where('order_type', $orderType);
        }

        $allOrders = $totalsQuery->with('payment.splits')->get();

        // Calculate payment breakdown
        $totalSubtotal = 0;
        $totalCash = 0;
        $totalCard = 0;
        $totalCredit = 0;
        $totalDiscount = 0;
        $totalTax = 0;
        $totalServiceCharge = 0;

        foreach ($allOrders as $order) {
            $totalSubtotal += $order->subtotal ?? 0;
            $totalDiscount += $order->discount_amount ?? 0;
            $totalTax += $order->tax_amount ?? 0;
            $totalServiceCharge += $order->service_charge ?? 0;

            // Get payment amounts and subtract change from cash only
            if ($order->payment) {
                $cashAmt = $order->payment->cash_amount ?? 0;
                $cardAmt = $order->payment->card_amount ?? 0;
                $creditAmt = $order->payment->credit_amount ?? 0;
                $changeAmt = $order->payment->change_amount ?? 0;

                // Subtract change from cash amount only
                $totalCash += max(0, $cashAmt - $changeAmt);
                $totalCard += $cardAmt;
                $totalCredit += $creditAmt;
            }
        }

        $totals = (object) [
            'total_transactions' => $allOrders->count(),
            'total_subtotal' => $totalSubtotal,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_service_charge' => $totalServiceCharge,
            'total_amount' => $allOrders->sum('total_amount'),
            'total_cash' => $totalCash,
            'total_card' => $totalCard,
            'total_credit' => $totalCredit,
        ];

        return view('sales-report.index', compact(
            'orders',
            'totals',
            'startDate',
            'endDate',
            'orderType'
        ));
    }

    /**
     * Get sale details and items for AJAX modal.
     */
    public function getSaleDetails(Order $order)
    {
        $order->load(['activeItems.item', 'activeItems.modifiers', 'payment.splits', 'waiter']);

        // Get payment amounts from specific columns
        $cashAmount = 0;
        $cardAmount = 0;
        $creditAmount = 0;

        if ($order->payment) {
            $cashAmount = $order->payment->cash_amount ?? 0;
            $cardAmount = $order->payment->card_amount ?? 0;
            $creditAmount = $order->payment->credit_amount ?? 0;
        }

        return response()->json([
            'order' => [
                'order_number' => $order->order_number,
                'payment_number' => $order->payment ? $order->payment->payment_number : 'N/A',
                'waiter_name' => $order->waiter ? $order->waiter->name : 'N/A',
                'customer_name' => $order->customer_name ?? 'Walk-in Customer',
                'order_type' => ucfirst($order->order_type),
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'discount_type' => $order->discount_type,
                'service_charge' => $order->service_charge,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment ? $order->payment->payment_method : 'N/A',
                'change_amount' => $order->payment ? $order->payment->change_amount : 0,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'credit_amount' => $creditAmount,
                'completed_at' => $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : 'N/A',
            ],
            'items' => $order->activeItems->filter(function ($item) {
                // Filter out items with 0 quantity
                return $item->quantity > 0;
            })->map(function ($item) {
                $modifiersText = $item->modifiers->map(function ($modifier) {
                    return $modifier->modifier_name . ' (+' . number_format($modifier->price_adjustment, 2) . ')';
                })->join(', ');

                return [
                    'item_name' => $item->item_display_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'modifiers' => $modifiersText,
                    'subtotal' => $item->subtotal,
                ];
            })->values()
        ]);
    }

    /**
     * Display printable receipt.
     */
    public function receipt(Order $order)
    {
        $order->load([
            'orderItems' => function ($query) {
                $query->where('status', '!=', 'deleted')
                    ->orderBy('id', 'asc')  // Ensure consistent ordering
                    ->with(['item', 'modifiers']);
                // NO LIMIT - fetch ALL items for printing
            },
            'payment.splits',
            'waiter',
            'table'
        ]);

        // Get payment amounts from specific columns
        $cashAmount = 0;
        $cardAmount = 0;
        $creditAmount = 0;

        if ($order->payment) {
            $cashAmount = $order->payment->cash_amount ?? 0;
            $cardAmount = $order->payment->card_amount ?? 0;
            $creditAmount = $order->payment->credit_amount ?? 0;
        }

        return view('sales-report.receipt', compact('order', 'cashAmount', 'cardAmount', 'creditAmount'));
    }

    /**
     * Export sales report to Excel.
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));
        $orderType = $request->get('order_type');

        // Fetch filtered orders
        $query = Order::query()
            ->where('status', 'completed')
            ->where('is_paid', true)
            ->where('is_deleted', false);

        if ($startDate) {
            $query->whereDate('completed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('completed_at', '<=', $endDate);
        }

        if ($orderType) {
            $query->where('order_type', $orderType);
        }

        $orders = $query->with(['payment.splits', 'waiter'])
            ->orderBy('completed_at', 'desc')
            ->get();

        // Calculate totals
        $totalCash = 0;
        $totalCard = 0;
        $totalCredit = 0;
        $totalAmount = 0;

        // Create Excel spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'Order Number',
            'B1' => 'Order Type',
            'C1' => 'Payment Method',
            'D1' => 'Total',
            'E1' => 'Cash',
            'F1' => 'Card',
            'G1' => 'Credit',
            'H1' => 'Date & Time',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('4A90E2');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        // Data rows
        $row = 2;
        foreach ($orders as $order) {
            // Get payment amounts from specific columns and subtract change from cash
            $cashAmount = $order->payment ? $order->payment->cash_amount : 0;
            $cardAmount = $order->payment ? $order->payment->card_amount : 0;
            $creditAmount = $order->payment ? $order->payment->credit_amount : 0;
            $changeAmount = $order->payment ? $order->payment->change_amount : 0;

            // Subtract change from cash (net cash received)
            $displayCashAmount = max(0, $cashAmount - $changeAmount);

            $sheet->setCellValue('A' . $row, $order->order_number);
            $sheet->setCellValue('B' . $row, ucfirst($order->order_type));
            $sheet->setCellValue('C' . $row, $order->payment ? $order->payment->payment_method : 'N/A');
            $sheet->setCellValue('D' . $row, $order->total_amount);
            $sheet->setCellValue('E' . $row, $displayCashAmount);
            $sheet->setCellValue('F' . $row, $cardAmount);
            $sheet->setCellValue('G' . $row, $creditAmount);
            $sheet->setCellValue('H' . $row, $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : 'N/A');

            // Add to totals
            $totalAmount += $order->total_amount;
            $totalCash += $displayCashAmount;
            $totalCard += $cardAmount;
            $totalCredit += $creditAmount;

            $row++;
        }

        // Totals row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->setCellValue('D' . $row, $totalAmount);
        $sheet->setCellValue('E' . $row, $totalCash);
        $sheet->setCellValue('F' . $row, $totalCard);
        $sheet->setCellValue('G' . $row, $totalCredit);

        // Style totals row
        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':G' . $row)
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E3F2FD');

        // Format currency columns
        foreach (['D', 'E', 'F', 'G'] as $column) {
            $sheet->getStyle($column . '2:' . $column . $row)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Generate filename
        $filename = 'sales_report_' . $startDate . '_to_' . $endDate . '.xlsx';

        // Return streaming response
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Soft delete an order (mark as deleted).
     * Also restores stock that was deducted when the order was paid.
     */
    public function softDelete(Order $order)
    {
        try {
            DB::beginTransaction();

            // Only restore stock if order was paid (stock was deducted on payment)
            if ($order->is_paid) {
                // Load order items with item and category relationships
                $orderItems = $order->orderItems()
                    ->whereNotIn('status', ['cancelled', 'deleted'])
                    ->with(['item.category'])
                    ->get();

                foreach ($orderItems as $orderItem) {
                    if (!$orderItem->item || $orderItem->quantity <= 0) {
                        continue;
                    }

                    // Check if item is Finished Goods (restore FG stock)
                    // BOTH conditions must be true: is_finished_goods AND is_stock_count
                    // No fallback by category - only explicit checkbox settings matter
                    $isFinishedGoodsItem = $orderItem->item->is_finished_goods && $orderItem->item->is_stock_count;

                    // Restore Finished Goods stock ONLY if item has BOTH checkboxes checked
                    if ($isFinishedGoodsItem) {
                        // Use stored item_modifier_id for ID-based matching (most reliable)
                        $modifierId = $orderItem->item_modifier_id;

                        // Get the sub stock BEFORE restoring to record the before quantity
                        $mainStockItem = \App\Models\MainStockItem::where('item_type', 'finished_good')
                            ->where('is_active', true)
                            ->where('linked_item_id', $orderItem->item_id)
                            ->where('linked_item_modifier_id', $modifierId)
                            ->first();

                        $qtyBefore = 0;
                        if ($mainStockItem) {
                            $subStockCheck = \App\Models\CashierSubStock::where('main_stock_item_id', $mainStockItem->id)->first();
                            $qtyBefore = $subStockCheck ? (float) $subStockCheck->quantity : 0;
                        }

                        // Restore stock using ID-based matching
                        $result = \App\Models\CashierSubStock::restoreForSaleById(
                            $orderItem->item_id,
                            $modifierId,
                            $orderItem->quantity,
                            auth()->id()
                        );

                        // Log FG stock restore
                        if ($result && $result->mainStockItem) {
                            \App\Models\CashierFgStockLog::log(
                                $result->main_stock_item_id,
                                'sale_restore',
                                $qtyBefore,
                                (float) $result->quantity,
                                'order',
                                $order->order_number,
                                'Stock restored - Order deleted - Qty: ' . $orderItem->quantity,
                                auth()->id()
                            );
                        }
                    }

                    // Restore Raw Materials stock (for non-finished goods with recipes)
                    if (!$orderItem->item->is_finished_goods && $orderItem->item->is_stock_count) {
                        $displayName = $orderItem->item_display_name ?? $orderItem->item->name;

                        // Extract modifier/portion ID from display name
                        $modifierId = null;
                        if (preg_match('/\(([^)]+)\)$/', $displayName, $matches)) {
                            $modifierName = trim($matches[1]);
                            $modifier = \App\Models\ItemModifier::where('item_id', $orderItem->item_id)
                                ->where('name', $modifierName)
                                ->first();
                            if ($modifier) {
                                $modifierId = $modifier->id;
                            }
                        }

                        // Get recipes - portion-specific if modifier exists
                        $recipes = \App\Models\ItemRecipe::where('item_id', $orderItem->item_id)
                            ->where('item_modifier_id', $modifierId)
                            ->get();

                        // Fallback to item-level recipes
                        if ($recipes->isEmpty() && $modifierId) {
                            $recipes = \App\Models\ItemRecipe::where('item_id', $orderItem->item_id)
                                ->whereNull('item_modifier_id')
                                ->get();
                        }

                        if ($recipes->isEmpty() && !$modifierId) {
                            $recipes = \App\Models\ItemRecipe::where('item_id', $orderItem->item_id)
                                ->whereNull('item_modifier_id')
                                ->get();
                        }

                        // Add back each raw material to CashierSubStock
                        foreach ($recipes as $recipe) {
                            $totalQuantity = $recipe->quantity * $orderItem->quantity;
                            $subStock = \App\Models\CashierSubStock::getOrCreateForItem($recipe->main_stock_item_id);
                            $qtyBefore = (float) $subStock->quantity;
                            $subStock->addStock($totalQuantity, auth()->id());

                            // Log RM stock restore
                            \App\Models\CashierRmStockLog::log(
                                $recipe->main_stock_item_id,
                                'sale_restore',
                                $qtyBefore,
                                (float) $subStock->quantity,
                                'order',
                                $order->order_number,
                                'Stock restored - Order deleted - Qty: ' . $totalQuantity,
                                auth()->id()
                            );
                        }
                    }
                }
            }

            // Mark order as deleted
            $order->is_deleted = true;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully and stock restored'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order: ' . $e->getMessage()
            ], 500);
        }
    }
}
