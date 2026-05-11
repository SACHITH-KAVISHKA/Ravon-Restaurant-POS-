<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDeliveredReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $orderTypes = $this->orderTypeOptions();

        $query = $this->buildReportQuery($filters);

        $orders = $query
            ->with(['payment:id,order_id,payment_status,payment_method,processed_at,updated_at'])
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->paginate(100)
            ->withQueryString();

        $summary = $this->buildSummary(clone $query);

        return view('super-admin-reports.order-delivered', compact(
            'orders',
            'summary',
            'orderTypes',
            'filters'
        ));
    }

    public function details(Order $order): JsonResponse
    {
        abort_unless(Auth::user()?->hasRole('superadmin'), 403);

        $order->load([
            'payment:id,order_id,payment_status,payment_method,processed_at,updated_at',
            'table:id,table_number',
            'orderItems' => function ($query) {
                $query->where('status', '!=', 'deleted')
                    ->with(['item:id,name'])
                    ->orderBy('id');
            },
        ]);

        $items = $order->orderItems->map(function ($orderItem) {
            $deliveredQuantity = (int) ($orderItem->delivered_quantity ?? 0);
            $totalQuantity = (int) ($orderItem->quantity ?? 0);
            $status = $deliveredQuantity >= $totalQuantity ? 'Delivered' : 'Preparing';

            return [
                'item_name' => $orderItem->item_display_name
                    ?? $orderItem->item?->name
                    ?? 'N/A',
                'all_quantity' => $totalQuantity,
                'delivered_quantity' => $deliveredQuantity,
                'start_prepare_time' => $orderItem->created_at?->format('H:i:s') ?? '-',
                'first_delivery_time' => $orderItem->delivered_at?->format('H:i:s') ?? '-',
                'last_prepare_time_start' => $orderItem->preparing_at?->format('H:i:s') ?? '-',
                'last_quantity_deliver_time' => $orderItem->last_delivered_at?->format('H:i:s')
                    ?? $orderItem->delivered_at?->format('H:i:s')
                    ?? '-',
                'status' => $status,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'order' => [
                'order_number' => $order->order_number,
                'table_id' => $order->table?->table_number ?? 'N/A',
                'date' => $order->created_at?->format('Y-m-d') ?? '-',
                'order_type' => ucfirst(str_replace('_', ' ', (string) $order->order_type)),
                'paid_status' => $this->isPaidOrder($order) ? 'Paid' : 'Unpaid',
                'subtotal' => number_format((float) ($order->subtotal ?? 0), 2),
                'created_time' => $order->created_at?->format('H:i:s') ?? '-',
                'closed_time' => $this->resolveClosedTime($order)?->format('H:i:s') ?? '-',
                'status' => $this->resolveOrderStatus($order)['label'],
            ],
            'items' => $items,
        ]);
    }

    public function print(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $query = $this->buildReportQuery($filters);

        $orders = $query
            ->with(['payment:id,order_id,payment_status,payment_method,processed_at,updated_at'])
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->get();

        $summary = $this->buildSummary(clone $query);

        return view('super-admin-reports.order-delivered-print', compact('orders', 'summary', 'filters'));
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $query = $this->buildReportQuery($filters);

        $orders = $query
            ->with(['payment:id,order_id,payment_status,payment_method,processed_at,updated_at'])
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Order Delivered Report');

        $headers = [
            'A1' => 'Order Number',
            'B1' => 'Table ID',
            'C1' => 'Date',
            'D1' => 'Order Type',
            'E1' => 'Paid or Not',
            'F1' => 'Sub Total',
            'G1' => 'Order Created Time',
            'H1' => 'Order Closed Time',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('764ba2');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $row = 2;
        foreach ($orders as $order) {
            $resolved = $this->resolveOrderStatus($order);

            $sheet->setCellValue('A' . $row, $order->order_number);
            $sheet->setCellValue('B' . $row, $order->table?->table_number ?? 'N/A');
            $sheet->setCellValue('C' . $row, $order->created_at?->format('Y-m-d') ?? '-');
            $sheet->setCellValue('D' . $row, ucfirst(str_replace('_', ' ', (string) $order->order_type)));
            $sheet->setCellValue('E' . $row, $this->isPaidOrder($order) ? 'Paid' : 'Unpaid');
            $sheet->setCellValue('F' . $row, (float) ($order->subtotal ?? 0));
            $sheet->setCellValue('G' . $row, $order->created_at?->format('H:i:s') ?? '-');
            $sheet->setCellValue('H' . $row, $this->resolveClosedTime($order)?->format('H:i:s') ?? '-');

            $row++;
        }

        $lastRow = max($row - 1, 2);
        $sheet->getStyle('F2:F' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'order_delivered_report_' . $filters['start_date'] . '_to_' . $filters['end_date'] . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function resolveFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'order_type' => 'nullable|string',
            'payment_status' => 'nullable|in:all,paid,unpaid',
        ]);

        return [
            'start_date' => Carbon::parse($validated['start_date'] ?? now()->toDateString())->toDateString(),
            'end_date' => Carbon::parse($validated['end_date'] ?? now()->toDateString())->toDateString(),
            'order_type' => $validated['order_type'] ?? '',
            'payment_status' => $validated['payment_status'] ?? 'all',
        ];
    }

    private function buildReportQuery(array $filters)
    {
        $itemStats = OrderItem::query()
            ->select('order_id')
            ->selectRaw('SUM(quantity) as total_item_quantity')
            ->selectRaw('SUM(COALESCE(delivered_quantity, 0)) as delivered_item_quantity')
            ->selectRaw('MIN(delivered_at) as first_delivery_at')
            ->selectRaw('MAX(preparing_at) as last_prepare_at')
            ->selectRaw('MAX(COALESCE(last_delivered_at, delivered_at)) as last_delivery_at')
            ->where('status', '!=', 'deleted')
            ->groupBy('order_id');

        $query = Order::query()
            ->select('orders.*')
            ->selectRaw('COALESCE(order_item_stats.total_item_quantity, 0) as total_item_quantity')
            ->selectRaw('COALESCE(order_item_stats.delivered_item_quantity, 0) as delivered_item_quantity')
            ->selectRaw('GREATEST(COALESCE(order_item_stats.total_item_quantity, 0) - COALESCE(order_item_stats.delivered_item_quantity, 0), 0) as pending_item_quantity')
            ->selectRaw('order_item_stats.first_delivery_at')
            ->selectRaw('order_item_stats.last_prepare_at')
            ->selectRaw('order_item_stats.last_delivery_at')
            ->joinSub($itemStats, 'order_item_stats', function ($join) {
                $join->on('orders.id', '=', 'order_item_stats.order_id');
            })
            ->where('orders.is_deleted', false)
            ->whereBetween('orders.created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);

        if (!empty($filters['order_type'])) {
            $query->where('orders.order_type', $filters['order_type']);
        }

        if ($filters['payment_status'] === 'paid') {
            $query->where(function ($paymentQuery) {
                $paymentQuery->where('orders.is_paid', true)
                    ->orWhereHas('payment', function ($paidPaymentQuery) {
                        $paidPaymentQuery->where('payment_status', 'completed');
                    });
            });
        }

        if ($filters['payment_status'] === 'unpaid') {
            $query->where(function ($paymentQuery) {
                $paymentQuery->where('orders.is_paid', false)
                    ->where(function ($statusQuery) {
                        $statusQuery->whereDoesntHave('payment', function ($unpaidPaymentQuery) {
                            $unpaidPaymentQuery->where('payment_status', 'completed');
                        })->orWhereHas('payment', function ($unpaidPaymentQuery) {
                            $unpaidPaymentQuery->where('payment_status', '!=', 'completed');
                        });
                    });
            });
        }

        return $query;
    }

    private function buildSummary($query): array
    {
        $orders = $query
            ->with(['payment:id,order_id,payment_status,payment_method,processed_at,updated_at'])
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->get();

        $totalSubtotal = 0;
        $paidOrders = 0;
        $unpaidOrders = 0;
        $completedOrders = 0;
        $incompleteOrders = 0;

        foreach ($orders as $order) {
            $totalSubtotal += (float) ($order->subtotal ?? 0);

            if ($this->isPaidOrder($order)) {
                $paidOrders++;
            } else {
                $unpaidOrders++;
            }

            if ($this->resolveOrderStatus($order)['key'] === 'completed') {
                $completedOrders++;
            } else {
                $incompleteOrders++;
            }
        }

        return [
            'total_orders' => $orders->count(),
            'total_subtotal' => $totalSubtotal,
            'paid_orders' => $paidOrders,
            'unpaid_orders' => $unpaidOrders,
            'completed_orders' => $completedOrders,
            'incomplete_orders' => $incompleteOrders,
        ];
    }

    private function resolveOrderStatus(Order $order): array
    {
        $totalQuantity = (int) ($order->total_item_quantity ?? 0);
        $deliveredQuantity = (int) ($order->delivered_item_quantity ?? 0);
        $isCompleted = $totalQuantity > 0 && $deliveredQuantity >= $totalQuantity;

        return [
            'key' => $isCompleted ? 'completed' : 'incomplete',
            'label' => $isCompleted ? 'Completed' : 'Not Yet Completed',
        ];
    }

    private function isPaidOrder(Order $order): bool
    {
        return (bool) ($order->payment?->payment_status === 'completed' || $order->is_paid);
    }

    private function resolveClosedTime(Order $order): ?Carbon
    {
        if (!$this->isPaidOrder($order) && $order->status !== 'completed') {
            return null;
        }

        return $order->updated_at ? Carbon::parse($order->updated_at) : null;
    }

    private function orderTypeOptions(): array
    {
        return [
            'dine_in' => 'Dine In',
            'takeaway' => 'Take Away',
            'delivery' => 'Delivery',
            'uber_eats' => 'Uber Eats',
            'pickme' => 'PickMe',
        ];
    }
}
