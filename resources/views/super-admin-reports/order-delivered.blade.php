@extends('layouts.app')

@section('title', 'Item Preparation Performance Report - Ravon Restaurant POS')

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .btn-action {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        transition: all 0.2s;
    }

    .badge-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.7);
    }

    .summary-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.25);
    }
</style>
@endpush

@section('content')
<div class="flex h-screen overflow-hidden">
    <x-sidebar />

    <div class="flex-1 overflow-y-auto">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">Item Preparation Performance Report</h1>
                <p class="text-gray-600">Analyze kitchen preparation times per menu item to identify bottlenecks and optimize performance.</p>
            </div>

            {{-- Filter Section (kept exactly as original) --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="{{ route('super-admin-reports.order-delivered.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] }}" max="{{ now()->toDateString() }}" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            @error('start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] }}" max="{{ now()->toDateString() }}" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            @error('end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="order_type" class="block text-sm font-medium text-gray-700 mb-1">Order Type</label>
                            <select id="order_type" name="order_type" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="" {{ empty($filters['order_type']) ? 'selected' : '' }}>All</option>
                                @foreach($orderTypes as $value => $label)
                                    <option value="{{ $value }}" {{ $filters['order_type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="performance_check" class="block text-sm font-medium text-gray-700 mb-1">Performance Check</label>
                            <select id="performance_check" name="performance_check" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="all" {{ $filters['performance_check'] === 'all' ? 'selected' : '' }}>All</option>
                                <option value="high" {{ $filters['performance_check'] === 'high' ? 'selected' : '' }}>High</option>
                                <option value="normal" {{ $filters['performance_check'] === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="slow" {{ $filters['performance_check'] === 'slow' ? 'selected' : '' }}>Slow</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:col-span-2">
                            <button type="submit" class="w-full bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search
                            </button>
                            <a href="{{ route('super-admin-reports.order-delivered.export', request()->query()) }}" class="w-full bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 mb-6">
                {{-- Fastest Item --}}
                <div class="summary-card bg-white border border-gray-200 rounded-xl shadow-md p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Fastest Item</h3>
                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-gray-800 truncate">{{ $summary['fastest_item']['name'] }}</p>
                    <p class="text-sm text-green-600 font-semibold">{{ $summary['fastest_item']['time'] }} mins</p>
                </div>

                {{-- Slowest Item --}}
                <div class="summary-card bg-white border border-gray-200 rounded-xl shadow-md p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Slowest Item</h3>
                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-gray-800 truncate">{{ $summary['slowest_item']['name'] }}</p>
                    <p class="text-sm text-red-600 font-semibold">{{ $summary['slowest_item']['time'] }} mins</p>
                </div>
            </div>

            {{-- Report Table --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Times Ordered</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Total Quantity</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Avg Prep Time</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Fastest Time</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Slowest Time</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Performance</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($items as $item)
                                @php
                                    $avgPrepTime = (int) ($item->avg_prep_time ?? 0);
                                    if ($avgPrepTime < 10) {
                                        $perfLabel = 'High';
                                        $perfClass = 'bg-green-100 text-green-700';
                                    } elseif ($avgPrepTime <= 20) {
                                        $perfLabel = 'Normal';
                                        $perfClass = 'bg-yellow-100 text-yellow-700';
                                    } else {
                                        $perfLabel = 'Slow';
                                        $perfClass = 'bg-red-100 text-red-700';
                                    }
                                @endphp
                                <tr class="hover:bg-purple-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-purple-600 font-semibold">{{ $item->item_name }}</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-gray-700">{{ number_format($item->times_ordered) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-gray-700">{{ number_format($item->total_quantity) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center font-semibold text-gray-800">{{ $avgPrepTime }} min</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-gray-600">{{ (int) ($item->min_prep_time ?? 0) }} min</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-gray-600">{{ (int) ($item->max_prep_time ?? 0) }} min</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-block px-3 py-1 text-xs font-bold rounded-md {{ $perfClass }}">{{ $perfLabel }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <button class="btn-action bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white view-details-btn" data-item-id="{{ $item->item_id }}" data-item-modifier-id="{{ $item->item_modifier_id }}" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-gray-600 text-lg">No Data Found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Item Details Modal --}}
    <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="itemDetailsModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="modal-backdrop fixed inset-0"></div>
            <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 max-w-3xl w-full">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-purple-100">
                    <h3 class="text-xl font-bold text-purple-700">Item Preparation Details</h3>
                    <button class="text-gray-500 hover:text-gray-700 close-modal">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div id="details-loading" class="flex items-center justify-center py-10">
                        <div class="flex flex-col items-center justify-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                            <p class="text-gray-600">Loading item details...</p>
                        </div>
                    </div>

                    <div id="details-content" class="hidden space-y-6">
                        {{-- Item Name Header --}}
                        <div class="text-center">
                            <h4 class="text-2xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent" id="modal-item-name">-</h4>
                        </div>

                        {{-- Preparation Statistics --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-purple-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600 mb-1">Average Time</p>
                                <p class="text-xl font-bold text-purple-700" id="modal-avg-time">-</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600 mb-1">Fastest Time</p>
                                <p class="text-xl font-bold text-green-700" id="modal-min-time">-</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600 mb-1">Slowest Time</p>
                                <p class="text-xl font-bold text-red-700" id="modal-max-time">-</p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600 mb-1">Total Quantity</p>
                                <p class="text-xl font-bold text-blue-700" id="modal-total-qty">-</p>
                            </div>
                        </div>

                        {{-- Preparation Records Table --}}
                        <div>
                            <h5 class="text-lg font-semibold text-gray-700 mb-3">Preparation Records</h5>
                            <div class="bg-gray-50 rounded-lg overflow-hidden">
                                <table class="w-full">
                                    <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Order ID</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Prepared At</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Delivered At</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Kitchen Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modal-orders-body" class="divide-y divide-gray-200"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button class="bg-white hover:bg-gray-100 text-gray-700 font-semibold py-2 px-6 rounded-lg transition close-modal border border-gray-300">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('.view-details-btn').on('click', function () {
            const itemId = $(this).data('item-id');
            const itemModifierId = $(this).data('item-modifier-id');

            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            const orderType = $('#order_type').val();
            const performanceCheck = $('#performance_check').val();
            const paymentStatus = 'all';
            const params = $.param({
                item_id: itemId,
                item_modifier_id: itemModifierId || '',
                start_date: startDate,
                end_date: endDate,
                order_type: orderType,
                performance_check: performanceCheck,
                payment_status: paymentStatus
            });

            $('#itemDetailsModal').removeClass('hidden');
            $('#details-loading').removeClass('hidden');
            $('#details-content').addClass('hidden');

            $.get(`/super-admin-reports/order-delivered/item-details?${params}`)
                .done(function (response) {
                    if (!response.success) {
                        throw new Error('Unable to load item details');
                    }

                    const item = response.item;
                    const records = response.preparation_records || [];

                    $('#modal-item-name').text(item.name ?? '-');
                    $('#modal-avg-time').text((item.avg_prep_time ?? 0) + ' min');
                    $('#modal-min-time').text((item.min_prep_time ?? 0) + ' min');
                    $('#modal-max-time').text((item.max_prep_time ?? 0) + ' min');
                    $('#modal-total-qty').text(Number(item.total_quantity ?? 0).toLocaleString());

                    let rows = '';
                    if (records.length === 0) {
                        rows = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No preparation records found</td></tr>';
                    } else {
                        records.forEach(function (record) {
                            rows += `
                                <tr class="bg-white hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-800">
                                        <span class="text-purple-600 font-mono font-semibold">${escapeHtml(record.order_number)}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(record.prepared_at)}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(record.delivered_at)}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-800">${escapeHtml(record.kitchen_time)} min</td>
                                </tr>
                            `;
                        });
                    }

                    $('#modal-orders-body').html(rows);
                    $('#details-loading').addClass('hidden');
                    $('#details-content').removeClass('hidden');
                })
                .fail(function () {
                    $('#details-loading').addClass('hidden');
                    $('#details-content').removeClass('hidden');
                    $('#modal-orders-body').html('<tr><td colspan="4" class="px-4 py-8 text-center text-red-600">Failed to load item details</td></tr>');
                });
        });

        $('.close-modal').on('click', function () {
            $('#itemDetailsModal').addClass('hidden');
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('#itemDetailsModal').addClass('hidden');
            }
        });
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endpush
@endsection
