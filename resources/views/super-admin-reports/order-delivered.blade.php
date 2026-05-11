@extends('layouts.app')

@section('title', 'Order Delivered Report - Ravon Restaurant POS')

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
</style>
@endpush

@section('content')
<div class="flex h-screen overflow-hidden">
    <x-sidebar />

    <div class="flex-1 overflow-y-auto">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">Order Delivered Report</h1>
                <p class="text-gray-600">Track completed and incomplete order delivery progress for Super Admin users.</p>
            </div>

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
                            <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Paid or Not</label>
                            <select id="payment_status" name="payment_status" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="all" {{ $filters['payment_status'] === 'all' ? 'selected' : '' }}>All</option>
                                <option value="paid" {{ $filters['payment_status'] === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="unpaid" {{ $filters['payment_status'] === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
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

            <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Order Number</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Table ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Order Type</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Paid or Not</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">Sub Total</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Order Created Time</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Order Closed Time</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($orders as $order)
                                @php
                                    $paymentBadge = ($order->payment?->payment_status === 'completed' || $order->is_paid) ? 'Paid' : 'Unpaid';
                                @endphp
                                <tr class="hover:bg-purple-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-purple-600 font-mono font-semibold">{{ $order->order_number }}</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $order->table?->table_number ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm">{{ $order->created_at?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="badge-status bg-blue-600/20 text-blue-600">{{ ucfirst(str_replace('_', ' ', $order->order_type)) }}</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($paymentBadge === 'Paid')
                                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-green-100 text-green-700">Paid</span>
                                        @else
                                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-gray-100 text-gray-700">Unpaid</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-gray-800">LKR {{ number_format($order->subtotal ?? 0, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm">{{ $order->created_at?->format('H:i:s') ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm">{{ $order->updated_at?->format('H:i:s') ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <button class="btn-action bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white view-details-btn" data-order-id="{{ $order->id }}" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
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

                @if($orders->hasPages())
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="orderDetailsModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="modal-backdrop fixed inset-0"></div>
            <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 max-w-6xl w-full">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-purple-100">
                    <h3 class="text-xl font-bold text-purple-700">Order Details</h3>
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
                            <p class="text-gray-600">Loading order details...</p>
                        </div>
                    </div>

                    <div id="details-content" class="hidden space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><p class="text-sm text-gray-600">Order Number</p><p class="text-lg font-semibold text-gray-800" id="modal-order-id">-</p></div>
                            <div><p class="text-sm text-gray-600">Table ID</p><p class="text-lg font-semibold text-gray-800" id="modal-table-id">-</p></div>
                            <div><p class="text-sm text-gray-600">Date</p><p class="text-lg font-semibold text-gray-800" id="modal-date">-</p></div>
                            <div><p class="text-sm text-gray-600">Order Type</p><p class="text-lg font-semibold text-gray-800" id="modal-order-type">-</p></div>
                            <div><p class="text-sm text-gray-600">Paid or Not</p><p class="text-lg font-semibold text-gray-800" id="modal-paid-status">-</p></div>
                            <div><p class="text-sm text-gray-600">Status</p><p class="text-lg font-semibold text-gray-800" id="modal-status">-</p></div>
                        </div>

                        <div class="bg-gray-50 rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Item Name</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">All Quantity</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Delivered Quantity</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Start Prepare Item Time</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">First Delivery Time</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Last Prepare Time Start</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Last Quantity Deliver Time</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-items-body" class="divide-y divide-gray-200"></tbody>
                            </table>
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
            const orderId = $(this).data('order-id');

            $('#orderDetailsModal').removeClass('hidden');
            $('#details-loading').removeClass('hidden');
            $('#details-content').addClass('hidden');

            $.get(`/super-admin-reports/order-delivered/${orderId}/details`)
                .done(function (response) {
                    if (!response.success) {
                        throw new Error('Unable to load order details');
                    }

                    const order = response.order;
                    const items = response.items || [];

                    $('#modal-order-id').text(order.order_number ?? '-');
                    $('#modal-table-id').text(order.table_id ?? 'N/A');
                    $('#modal-date').text(order.date ?? '-');
                    $('#modal-order-type').text(order.order_type ?? '-');
                    $('#modal-paid-status').html(order.paid_status === 'Paid'
                        ? '<span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-green-100 text-green-700">Paid</span>'
                        : '<span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-gray-100 text-gray-700">Unpaid</span>');
                    $('#modal-status').html(order.status === 'Completed'
                        ? '<span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-green-100 text-green-700">Completed</span>'
                        : '<span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-orange-100 text-orange-700">Not Yet Completed</span>');

                    let rows = '';
                    if (items.length === 0) {
                        rows = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No Data Found</td></tr>';
                    } else {
                        items.forEach(function (item) {
                            const itemStatusClass = item.status === 'Delivered'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-orange-100 text-orange-700';

                            rows += `
                                <tr class="bg-white hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-800">${escapeHtml(item.item_name)}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(item.all_quantity)}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(item.delivered_quantity)}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(item.start_prepare_time)}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(item.first_delivery_time)}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(item.last_prepare_time_start)}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">${escapeHtml(item.last_quantity_deliver_time)}</td>
                                    <td class="px-4 py-3 text-center"><span class="inline-block px-3 py-1 text-xs font-bold rounded-md ${itemStatusClass}">${escapeHtml(item.status)}</span></td>
                                </tr>
                            `;
                        });
                    }

                    $('#modal-items-body').html(rows);
                    $('#details-loading').addClass('hidden');
                    $('#details-content').removeClass('hidden');
                })
                .fail(function () {
                    $('#details-loading').addClass('hidden');
                    $('#details-content').removeClass('hidden');
                    $('#modal-items-body').html('<tr><td colspan="8" class="px-4 py-8 text-center text-red-600">Failed to load order details</td></tr>');
                });
        });

        $('.close-modal').on('click', function () {
            $('#orderDetailsModal').addClass('hidden');
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('#orderDetailsModal').addClass('hidden');
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
