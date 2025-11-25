<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Table;
use App\Models\Payment;
use App\Models\Kot;
use Illuminate\Http\Request;
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
        
        return view('dashboard', compact('stats', 'recentOrders', 'topItems', 'hourlySales'));
    }
}
