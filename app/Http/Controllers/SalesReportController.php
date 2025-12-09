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
        $order->load(['orderItems.item', 'orderItems.modifiers', 'payment.splits', 'waiter']);

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
            'items' => $order->orderItems->map(function ($item) {
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
            })
        ]);
    }

    /**
     * Display printable receipt.
     */
    public function receipt(Order $order)
    {
        $order->load(['orderItems.item', 'orderItems.modifiers', 'payment.splits', 'waiter', 'table']);

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
        $totalSubtotal = 0;
        $totalCash = 0;
        $totalCard = 0;
        $totalDiscount = 0;
        $totalTax = 0;
        $totalServiceCharge = 0;
        $totalAmount = 0;

        // Create Excel spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'Order Number',
            'B1' => 'Payment Number',
            'C1' => 'Waiter',
            'D1' => 'Customer',
            'E1' => 'Order Type',
            'F1' => 'Subtotal',
            'G1' => 'Discount',
            'H1' => 'Service Charge',
            'I1' => 'Tax',
            'J1' => 'Total',
            'K1' => 'Cash',
            'L1' => 'Card',
            'M1' => 'Date & Time',
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
            $sheet->setCellValue('B' . $row, $order->payment ? $order->payment->payment_number : 'N/A');
            $sheet->setCellValue('C' . $row, $order->waiter ? $order->waiter->name : 'N/A');
            $sheet->setCellValue('D' . $row, $order->customer_name ?? 'Walk-in');
            $sheet->setCellValue('E' . $row, ucfirst($order->order_type));
            $sheet->setCellValue('F' . $row, $order->subtotal);
            $sheet->setCellValue('G' . $row, $order->discount_amount);
            $sheet->setCellValue('H' . $row, $order->service_charge);
            $sheet->setCellValue('I' . $row, $order->tax_amount);
            $sheet->setCellValue('J' . $row, $order->total_amount);
            $sheet->setCellValue('K' . $row, $displayCashAmount);
            $sheet->setCellValue('L' . $row, $cardAmount);
            $sheet->setCellValue('M' . $row, $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : 'N/A');

            // Add to totals
            $totalSubtotal += $order->subtotal;
            $totalDiscount += $order->discount_amount;
            $totalServiceCharge += $order->service_charge;
            $totalTax += $order->tax_amount;
            $totalAmount += $order->total_amount;
            $totalCash += $displayCashAmount;
            $totalCard += $cardAmount;

            $row++;
        }

        // Totals row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('F' . $row, $totalSubtotal);
        $sheet->setCellValue('G' . $row, $totalDiscount);
        $sheet->setCellValue('H' . $row, $totalServiceCharge);
        $sheet->setCellValue('I' . $row, $totalTax);
        $sheet->setCellValue('J' . $row, $totalAmount);
        $sheet->setCellValue('K' . $row, $totalCash);
        $sheet->setCellValue('L' . $row, $totalCard);

        // Style totals row
        $sheet->getStyle('A' . $row . ':M' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':M' . $row)
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E3F2FD');

        // Format currency columns
        foreach (['F', 'G', 'H', 'I', 'J', 'K', 'L'] as $column) {
            $sheet->getStyle($column . '2:' . $column . $row)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        // Auto-size columns
        foreach (range('A', 'M') as $column) {
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
     */
    public function softDelete(Order $order)
    {
        try {
            DB::beginTransaction();

            // Mark order as deleted
            $order->is_deleted = true;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
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
