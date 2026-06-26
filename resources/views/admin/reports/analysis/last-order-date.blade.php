@extends('layouts.app')

@section('title', 'Last Order Date Report - Ravon Restaurant POS')

@push('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .days-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .days-fresh {
            background: #dcfce7;
            color: #166534;
        }

        .days-moderate {
            background: #fef3c7;
            color: #92400e;
        }

        .days-stale {
            background: #fee2e2;
            color: #991b1b;
        }

        .days-never {
            background: #f3f4f6;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">
                        Last Order Date Report
                    </h1>
                    <div class="flex items-center text-sm text-gray-500 mt-1">
                        <span>Reports</span>
                        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span>Analysis</span>
                        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-purple-600 font-medium">Last Order Date</span>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form method="GET" action="{{ route('analysis.last-order-date') }}" class="space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
                            <!-- From Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                                    max="{{ now()->toDateString() }}"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- To Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                                    max="{{ now()->toDateString() }}"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Search -->
                            <div class="lg:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Item</label>
                                <input type="text" id="search" name="search" value="{{ $search }}"
                                    placeholder="Search Item Name..."
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:col-span-2">
                                <button type="submit"
                                    class="w-full bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Search
                                </button>
                                <a href="{{ route('analysis.last-order-date.export', request()->query()) }}"
                                    class="w-full bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Excel
                                </a>
                                <a href="{{ route('analysis.last-order-date') }}"
                                    class="w-full bg-white hover:bg-gray-100 text-gray-700 font-semibold py-2 px-4 rounded-lg transition border border-gray-300 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Results Table Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="table-responsive">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        #</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Item Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Category</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Last Sold Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Last Order No</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                        Quantity Sold</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                        Days Since Last Sale</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($items as $index => $item)
                                    <tr class="hover:bg-purple-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm">
                                            {{ ($items->currentPage() - 1) * $items->perPage() + $index + 1 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-gray-800 font-medium">{{ $item->display_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ $item->category_name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($item->last_order_date)
                                                <span class="text-gray-700">{{ \Carbon\Carbon::parse($item->last_order_date)->format('Y-m-d H:i') }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($item->last_order_number)
                                                <span class="text-purple-600 font-mono font-semibold">{{ $item->last_order_number }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($item->last_order_date)
                                                <span class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 text-purple-700 font-bold rounded-full">
                                                    {{ $item->quantity_sold }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">0</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($item->last_order_date)
                                                @php
                                                    $daysSince = (int) \Carbon\Carbon::parse($item->last_order_date)->diffInDays(now());
                                                @endphp
                                                <span class="days-badge {{ $daysSince <= 3 ? 'days-fresh' : ($daysSince <= 14 ? 'days-moderate' : 'days-stale') }}">
                                                    {{ $daysSince }} {{ $daysSince === 1 ? 'day' : 'days' }}
                                                </span>
                                            @else
                                                <span class="days-badge days-never">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="text-gray-600 text-lg">No matching items found.</p>
                                                <p class="text-gray-500 text-sm mt-1">Try adjusting your search criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($items->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} items
                                </div>
                                <div>
                                    {{ $items->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
