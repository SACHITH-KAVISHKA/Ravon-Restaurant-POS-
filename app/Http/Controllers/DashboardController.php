<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Table;
use App\Models\Payment;
use App\Models\Kot;
use App\Models\Item;
use App\Models\MainStockItem;
use App\Models\Wastage;
use App\Models\VoidRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        // Get today's statistics
        $stats = [
            'total_sales' => Payment::whereDate('created_at', $today)->sum('total_amount'),
            'total_orders' => Order::whereDate('created_at', $today)->count(),
            'active_tables' => Table::whereIn('status', ['ordered', 'serving', 'bill_requested'])->count(),
            'pending_kots' => Kot::where('status', 'pending')->count(),
        ];

        // Recent orders
        $recentOrders = Order::with(['table', 'waiter'])
            ->latest()
            ->take(10)
            ->get();

        // Top selling items today
        $topItems = DB::table('order_items')
            ->join('items', 'order_items.item_id', '=', 'items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $today)
            ->where('items.status', 1)
            ->select('items.name', DB::raw('SUM(order_items.quantity) as total_quantity'), DB::raw('SUM(order_items.subtotal) as total_sales'))
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        // Hourly sales for chart
        $hourlySales = Payment::whereDate('created_at', $today)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Super Admin only widgets
        $superAdminStats = null;
        $salesCharts = null;

        if (Auth::user() && Auth::user()->hasRole('superadmin')) {
            $currentMonthStart = now()->copy()->startOfMonth();
            $currentMonthEnd = now()->copy()->endOfMonth();
            $previousMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
            $previousMonthEnd = now()->copy()->subMonthNoOverflow()->endOfMonth();

            $superAdminStats = [
                // Active POS menu items (Item model has a global 'active' scope)
                'menu_item_count' => Item::count(),
                // Active raw material items
                'rm_item_count' => MainStockItem::ofType('raw_material')->active()->count(),
                // Wastage entries recorded this month
                'wastage_count' => Wastage::whereBetween('wastage_date', [$currentMonthStart, $currentMonthEnd])->count(),
                // Bills (orders) with voided items this month
                'void_bill_count' => VoidRecord::whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                    ->distinct('order_id')
                    ->count('order_id'),
            ];

            // Day-by-day sales (same source as Sales Summary page:
            // completed + paid + not deleted orders, by completed_at)
            $salesCharts = [
                'current' => $this->getDailySalesForMonth($currentMonthStart, $currentMonthEnd),
                'previous' => $this->getDailySalesForMonth($previousMonthStart, $previousMonthEnd),
            ];
        }

        return view('dashboard', compact(
            'stats',
            'recentOrders',
            'topItems',
            'hourlySales',
            'superAdminStats',
            'salesCharts'
        ));
    }

    /**
     * Get day-by-day sales totals for a month using the same
     * criteria as the Sales Summary (Sales Report) page.
     */
    private function getDailySalesForMonth(Carbon $start, Carbon $end): array
    {
        $dailyTotals = Order::query()
            ->where('status', 'completed')
            ->where('is_paid', true)
            ->where('is_deleted', false)
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->select(DB::raw('DATE(completed_at) as sale_date'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $labels = [];
        $values = [];
        $daysInMonth = $start->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $start->copy()->day($day)->format('Y-m-d');
            $labels[] = $day;
            $values[] = round((float) ($dailyTotals[$date] ?? 0), 2);
        }

        return [
            'month' => $start->format('F Y'),
            'total' => round(array_sum($values), 2),
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
