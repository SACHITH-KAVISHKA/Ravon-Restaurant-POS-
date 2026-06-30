@extends('layouts.app')

@section('title', 'Price List Activity - Ravon Restaurant POS')

@push('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
@endpush

@section('content')
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">
                            Price List Activity
                        </h1>
                        <p class="text-gray-600">Audit log of all POS item and portion price changes</p>
                    </div>
                    <!-- Export Excel Button -->
                    <div class="flex md:justify-end">
                        <a href="{{ route('menu.price-list-activity.export', request()->query()) }}"
                            class="bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2.5 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Excel
                        </a>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form action="{{ route('menu.price-list-activity.index') }}" method="GET" class="space-y-0">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <!-- From Date -->
                            <div class="md:col-span-3">
                                <label for="from_date" class="block text-sm font-medium text-gray-600 mb-2">From Date</label>
                                <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>

                            <!-- To Date -->
                            <div class="md:col-span-3">
                                <label for="to_date" class="block text-sm font-medium text-gray-600 mb-2">To Date</label>
                                <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>

                            <!-- Item Name Search -->
                            <div class="md:col-span-3">
                                <label for="item_name" class="block text-sm font-medium text-gray-600 mb-2">Search Item Name</label>
                                <input type="text" name="item_name" id="item_name" value="{{ request('item_name') }}" placeholder="e.g. Rice, Pizza"
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>

                            <!-- Filter Actions -->
                            <div class="md:col-span-3 flex gap-2">
                                <a href="{{ route('menu.price-list-activity.index') }}"
                                    class="w-1/2 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition duration-200 font-semibold text-sm whitespace-nowrap flex items-center justify-center">
                                    Clear
                                </a>
                                <button type="submit"
                                    class="w-1/2 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold text-sm whitespace-nowrap flex items-center justify-center">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Data Table Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="table-responsive">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                        <th class="text-left py-3 px-4 text-white font-semibold rounded-tl-lg">Date & Time</th>
                                        <th class="text-left py-3 px-4 text-white font-semibold">Item Name</th>
                                        <th class="text-left py-3 px-4 text-white font-semibold">Portion</th>
                                        <th class="text-right py-3 px-4 text-white font-semibold">Previous Price</th>
                                        <th class="text-right py-3 px-4 text-white font-semibold">New Price</th>
                                        <th class="text-right py-3 px-4 text-white font-semibold">Difference</th>
                                        <th class="text-left py-3 px-4 text-white font-semibold rounded-tr-lg">Changed By</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($activities as $activity)
                                        <tr class="hover:bg-purple-50 transition">
                                            <td class="py-3 px-4 text-gray-600 text-sm whitespace-nowrap">
                                                {{ $activity->created_at ? $activity->created_at->format('M d, Y H:i:s') : 'N/A' }}
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="text-gray-800 font-semibold">{{ $activity->item?->name ?? 'N/A' }}</span>
                                            </td>
                                            <td class="py-3 px-4">
                                                @if($activity->portion)
                                                    <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-medium">
                                                        {{ $activity->portion->name }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 text-xs italic">Main Item</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-right text-gray-700 font-medium whitespace-nowrap">
                                                Rs. {{ number_format($activity->previous_price, 2) }}
                                            </td>
                                            <td class="py-3 px-4 text-right text-green-600 font-bold whitespace-nowrap">
                                                Rs. {{ number_format($activity->new_price, 2) }}
                                            </td>
                                            <td class="py-3 px-4 text-right font-semibold whitespace-nowrap">
                                                @php
                                                    $diff = $activity->new_price - $activity->previous_price;
                                                @endphp
                                                 @if($diff > 0)
                                                     <span class="text-green-600">+Rs. {{ number_format($diff, 2) }}</span>
                                                 @else
                                                     <span class="text-red-500">-Rs. {{ number_format(abs($diff), 2) }}</span>
                                                 @endif
                                            </td>
                                            <td class="py-3 px-4 text-gray-800 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold text-xs">
                                                        {{ strtoupper(substr($activity->user?->name ?? 'S', 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-800">{{ $activity->user?->name ?? 'System' }}</div>
                                                        <div class="text-xs text-gray-500">{{ $activity->user?->username ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-12">
                                                <div class="flex flex-col items-center justify-center">
                                                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 00-2 2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    <p class="text-gray-600">No price list activities found matching the criteria.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($activities->hasPages())
                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                            {{ $activities->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
