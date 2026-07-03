@extends('layouts.app')

@section('title', 'Dashboard - Ravon POS')

@section('content')
<div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <!-- Welcome Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">
                    Welcome back, {{ Auth::user()->name }}!
                </h1>
                <p class="text-gray-600">Here's what's happening with your restaurant today.</p>
                
                @if(Auth::user()->hasRole('supervisor'))
                <div class="mt-3 inline-flex items-center bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white px-4 py-2 rounded-lg shadow-md">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span class="font-semibold">Your PIN:</span>
                    <span class="ml-2 text-2xl font-bold tracking-wider">{{ Auth::user()->dynamic_pin }}</span>
                </div>
                @endif
            </div>
            @if(Auth::user()->hasRole('cashier'))
            <div>
                <a href="{{ route('pos.index') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white text-lg font-bold rounded-xl shadow-2xl hover:shadow-purple-500/50 transform hover:scale-105 transition-all duration-200">
                    <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Open POS
                </a>
            </div>
            @endif
        </div>

        @if(Auth::user()->hasRole('superadmin') && isset($superAdminStats) && $superAdminStats)
        <!-- Super Admin Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- POS Menu Items -->
            <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-purple-700">POS Menu Items</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($superAdminStats['menu_item_count']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Active items</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-md flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- RM Items -->
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-blue-700">RM Items</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($superAdminStats['rm_item_count']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Raw materials</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-md flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Wastage This Month -->
            <div class="bg-gradient-to-br from-amber-50 to-white rounded-xl shadow-md p-6 border-l-4 border-amber-500 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-amber-700">Wastage Count</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($superAdminStats['wastage_count']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">This month</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-500 shadow-md flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Void Bills This Month -->
            <div class="bg-gradient-to-br from-red-50 to-white rounded-xl shadow-md p-6 border-l-4 border-red-500 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-red-700">Void Bills</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($superAdminStats['void_bill_count']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">This month</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 shadow-md flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Section Title -->
        <div class="flex items-center mb-4">
            <div class="w-1.5 h-6 rounded-full bg-gradient-to-b from-[#667eea] to-[#764ba2] mr-3"></div>
            <h2 class="text-xl font-bold text-gray-800">Sales Overview</h2>
        </div>

        <!-- Sales Line Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Current Month Sales -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-purple-500 to-purple-300"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-purple-500 mr-2"></span>
                            <h2 class="text-lg font-semibold text-gray-800">{{ $salesCharts['current']['month'] }}</h2>
                        </div>
                        <span class="text-sm font-bold text-purple-700 bg-purple-50 px-3 py-1.5 rounded-full">Total: {{ number_format($salesCharts['current']['total'], 2) }}</span>
                    </div>
                    <div class="relative h-72">
                        <canvas id="currentMonthSalesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Previous Month Sales -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-blue-500 to-blue-300"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                            <h2 class="text-lg font-semibold text-gray-800">{{ $salesCharts['previous']['month'] }}</h2>
                        </div>
                        <span class="text-sm font-bold text-blue-700 bg-blue-50 px-3 py-1.5 rounded-full">Total: {{ number_format($salesCharts['previous']['total'], 2) }}</span>
                    </div>
                    <div class="relative h-72">
                        <canvas id="previousMonthSalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- Dashboard Content Area -->
        <div class="grid grid-cols-1 gap-6">
            <!-- You can add dashboard widgets here in the future -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-purple-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-gray-600 text-lg">Dashboard widgets can be added here</p>
                    <p class="text-gray-400 text-sm mt-2">Use the sidebar to navigate to different sections</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@if(Auth::user()->hasRole('superadmin') && isset($salesCharts) && $salesCharts)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Simplify large values (e.g. 150000 -> 150K, 1200000 -> 1.2M)
    function simplifyValue(value) {
        if (Math.abs(value) >= 1000000) {
            return (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        }
        if (Math.abs(value) >= 1000) {
            return (value / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        return value;
    }

    function buildSalesChart(canvasId, labels, values, color, bgColor) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Sales',
                    data: values,
                    borderColor: color,
                    backgroundColor: bgColor,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                return 'Day ' + items[0].label;
                            },
                            label: function (context) {
                                return 'Sales: ' + Number(context.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Day of Month' },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Sales' },
                        ticks: {
                            callback: function (value) {
                                return simplifyValue(value);
                            }
                        }
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        buildSalesChart(
            'currentMonthSalesChart',
            @json($salesCharts['current']['labels']),
            @json($salesCharts['current']['values']),
            '#764ba2',
            'rgba(118, 75, 162, 0.10)'
        );

        buildSalesChart(
            'previousMonthSalesChart',
            @json($salesCharts['previous']['labels']),
            @json($salesCharts['previous']['values']),
            '#667eea',
            'rgba(102, 126, 234, 0.10)'
        );
    });
</script>
@endpush
@endif
@endsection