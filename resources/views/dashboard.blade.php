@extends('layouts.app')

@section('title', 'Dashboard - Ravon POS')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">
                Welcome back, {{ Auth::user()->name }}!
            </h1>
            <p class="text-gray-400">Here's what's happening with your restaurant today.</p>
        </div>
        @if(Auth::user()->hasRole('cashier') || Auth::user()->hasRole('admin'))
        <div>
            <a href="{{ route('pos.index') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-800 text-white text-lg font-bold rounded-xl shadow-2xl hover:from-blue-700 hover:to-blue-900 transform hover:scale-105 transition-all duration-200">
                <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Open POS
            </a>
        </div>
        @endif
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Sales Card -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl shadow-xl p-6 transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm font-medium mb-1">Total Sales</p>
                    <h3 class="text-3xl font-bold text-white">
                        Rs. {{ number_format($stats['total_sales'], 2) }}
                    </h3>
                    <p class="text-blue-200 text-xs mt-1">Today</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl shadow-xl p-6 border border-gray-700 transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Total Orders</p>
                    <h3 class="text-3xl font-bold text-white">{{ $stats['total_orders'] }}</h3>
                    <p class="text-gray-400 text-xs mt-1">Today</p>
                </div>
                <div class="bg-blue-600/20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Tables Card -->
        <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl shadow-xl p-6 border border-gray-700 transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Active Tables</p>
                    <h3 class="text-3xl font-bold text-white">{{ $stats['active_tables'] }}</h3>
                    <p class="text-gray-400 text-xs mt-1">Occupied</p>
                </div>
                <div class="bg-blue-600/20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending KOTs Card -->
        <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl shadow-xl p-6 border border-gray-700 transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Pending KOTs</p>
                    <h3 class="text-3xl font-bold text-white">{{ $stats['pending_kots'] }}</h3>
                    <p class="text-gray-400 text-xs mt-1">In Kitchen</p>
                </div>
                <div class="bg-blue-600/20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders -->
        <div class="lg:col-span-2 bg-gray-900/50 border border-gray-800 rounded-xl shadow-xl">
            <div class="p-6 border-b border-gray-800">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Recent Orders
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($recentOrders as $order)
                    <div class="flex items-center justify-between p-4 bg-black/30 rounded-lg border border-gray-800 hover:border-blue-600/50 transition">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg font-bold text-blue-400">{{ $order->order_number }}</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ 
                                    $order->status === 'completed' ? 'bg-green-500/20 text-green-400' : 
                                    ($order->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-blue-500/20 text-blue-400') 
                                }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-400 mt-1">
                                @if($order->table)
                                Table: {{ $order->table->table_number }} | 
                                @endif
                                Waiter: {{ $order->waiter->name }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-white">Rs. {{ number_format($order->total_amount, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ $order->created_at->format('h:i A') }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p>No orders yet today</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top Selling Items -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl shadow-xl">
            <div class="p-6 border-b border-gray-800">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Top Selling
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($topItems as $item)
                    <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg border border-gray-800">
                        <div class="flex-1">
                            <div class="font-semibold text-white">{{ $item->name }}</div>
                            <div class="text-xs text-gray-400">{{ $item->total_quantity }} sold</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-blue-400">Rs. {{ number_format($item->total_sales, 2) }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500">
                        <p>No sales data available</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
