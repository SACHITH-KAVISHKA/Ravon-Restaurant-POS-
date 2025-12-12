<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Models\DailyReport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate daily sales report.
     */
    public function getDailySalesReport($date = null)
    {
        $date = $date ?? today();

        $orders = Order::whereDate('created_at', $date)
            ->where('status', '!=', 'cancelled')
            ->with(['activeItems', 'payment'])
            ->get();

        return [
            'date' => $date,
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_amount'),
            'total_tax' => $orders->sum('tax_amount'),
            'total_discounts' => $orders->sum('discount_amount'),
            'total_service_charge' => $orders->sum('service_charge'),
            'average_order_value' => $orders->avg('total_amount'),
            'cancelled_orders' => Order::whereDate('created_at', $date)
                ->where('status', 'cancelled')
                ->count(),
            'payment_breakdown' => $this->getPaymentBreakdown($date),
            'order_type_breakdown' => $this->getOrderTypeBreakdown($date),
            'hourly_sales' => $this->getHourlySales($date),
        ];
    }

    /**
     * Get payment method breakdown.
     */
    protected function getPaymentBreakdown($date)
    {
        return Payment::whereDate('processed_at', $date)
            ->where('payment_status', 'completed')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_method => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            });
    }

    /**
     * Get order type breakdown.
     */
    protected function getOrderTypeBreakdown($date)
    {
        return Order::whereDate('created_at', $date)
            ->where('status', '!=', 'cancelled')
            ->select('order_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('order_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->order_type => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            });
    }

    /**
     * Get hourly sales.
     */
    protected function getHourlySales($date)
    {
        return Order::whereDate('created_at', $date)
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get item-wise sales report.
     */
    public function getItemWiseSalesReport($startDate, $endDate)
    {
        return OrderItem::join('items', 'order_items.item_id', '=', 'items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'items.id',
                'items.name',
                'categories.name as category',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('AVG(order_items.unit_price) as avg_price')
            )
            ->groupBy('items.id', 'items.name', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get category-wise sales report.
     */
    public function getCategoryWiseSalesReport($startDate, $endDate)
    {
        return OrderItem::join('items', 'order_items.item_id', '=', 'items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.item_id) as unique_items'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get staff performance report.
     */
    public function getStaffPerformanceReport($startDate, $endDate)
    {
        return User::role('waiter')
            ->select('users.*')
            ->withCount(['waiterOrders as total_orders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'cancelled');
            }])
            ->withSum(['waiterOrders as total_sales' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'cancelled');
            }], 'total_amount')
            ->withAvg(['waiterOrders as avg_order_value' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'cancelled');
            }], 'total_amount')
            ->orderByDesc('total_sales')
            ->get();
    }

    /**
     * Get table turnover report.
     */
    public function getTableTurnoverReport($date = null)
    {
        $date = $date ?? today();

        return Order::join('tables', 'orders.table_id', '=', 'tables.id')
            ->join('floors', 'tables.floor_id', '=', 'floors.id')
            ->whereDate('orders.created_at', $date)
            ->where('orders.status', '!=', 'cancelled')
            ->whereNotNull('orders.table_id')
            ->select(
                'tables.id',
                'tables.table_number',
                'floors.name as floor_name',
                DB::raw('COUNT(orders.id) as times_used'),
                DB::raw('SUM(orders.total_amount) as total_revenue'),
                DB::raw('AVG(orders.total_amount) as avg_order_value'),
                DB::raw('AVG(orders.guest_count) as avg_guests')
            )
            ->groupBy('tables.id', 'tables.table_number', 'floors.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get delivery performance report.
     */
    public function getDeliveryPerformanceReport($startDate, $endDate)
    {
        return Order::with('deliveryOrder')
            ->whereIn('order_type', ['delivery', 'uber_eats', 'pickme'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->select(
                'order_type',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('AVG(total_amount) as avg_order_value'),
                DB::raw('SUM(delivery_fee) as total_delivery_fees')
            )
            ->groupBy('order_type')
            ->get();
    }

    /**
     * Get cancelled orders report.
     */
    public function getCancelledOrdersReport($startDate, $endDate)
    {
        return Order::with(['cancelledBy', 'table'])
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->where('status', 'cancelled')
            ->select('*')
            ->orderByDesc('cancelled_at')
            ->get()
            ->map(function ($order) {
                return [
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'table' => $order->table?->table_number,
                    'total_amount' => $order->total_amount,
                    'cancelled_at' => $order->cancelled_at,
                    'cancelled_by' => $order->cancelledBy?->name,
                    'reason' => $order->cancellation_reason,
                ];
            });
    }

    /**
     * Get peak hours analysis.
     */
    public function getPeakHoursAnalysis($startDate, $endDate)
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as avg_order_value'),
                DB::raw('SUM(guest_count) as total_guests')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Generate and save daily report.
     */
    public function generateDailyReport($date = null)
    {
        $date = $date ?? today();
        
        return DailyReport::generateForDate($date);
    }

    /**
     * Get monthly summary.
     */
    public function getMonthlySummary($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get();

        return [
            'period' => $startDate->format('F Y'),
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_amount'),
            'total_tax' => $orders->sum('tax_amount'),
            'avg_daily_sales' => $orders->sum('total_amount') / $endDate->day,
            'daily_breakdown' => $this->getDailyBreakdown($startDate, $endDate),
            'top_items' => $this->getTopSellingItems($startDate, $endDate, 10),
            'payment_methods' => $this->getPaymentBreakdown($startDate),
        ];
    }

    /**
     * Get daily breakdown for a period.
     */
    protected function getDailyBreakdown($startDate, $endDate)
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top selling items.
     */
    protected function getTopSellingItems($startDate, $endDate, $limit = 10)
    {
        return OrderItem::join('items', 'order_items.item_id', '=', 'items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'items.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Export report to CSV.
     */
    public function exportToCSV(array $data, string $filename): string
    {
        $filepath = storage_path("app/reports/{$filename}");
        
        $file = fopen($filepath, 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);
        
        return $filepath;
    }
}
