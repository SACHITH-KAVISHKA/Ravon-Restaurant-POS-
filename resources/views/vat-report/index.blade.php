@extends('layouts.app')

@section('title', 'VAT Report - Ravon Restaurant POS')

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

    .vat-badge {
        background: linear-gradient(135deg, #1d7cf2, #0d5cc7);
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 9999px;
        letter-spacing: 0.05em;
        vertical-align: middle;
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
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#1d7cf2] to-[#0d5cc7] bg-clip-text text-transparent mb-2 flex items-center gap-3">
                    <svg class="w-8 h-8 text-[#1d7cf2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                    </svg>
                    VAT Report
                </h1>
                <p class="text-gray-600">Sales transactions with VAT customer details assigned</p>
            </div>

            <!-- Filter Card -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="{{ route('vat-report.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                                class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                                class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Order Type -->
                        <div>
                            <label for="order_type" class="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
                            <select id="order_type" name="order_type"
                                class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Types</option>
                                <option value="dine_in"  {{ $orderType == 'dine_in'  ? 'selected' : '' }}>Dine In</option>
                                <option value="takeaway" {{ $orderType == 'takeaway' ? 'selected' : '' }}>Takeaway</option>
                                <option value="delivery" {{ $orderType == 'delivery' ? 'selected' : '' }}>Delivery</option>
                                <option value="pickme"   {{ $orderType == 'pickme'   ? 'selected' : '' }}>PickMe</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end space-x-2">
                            <button type="submit"
                                class="flex-1 bg-gradient-to-r from-[#1d7cf2] to-[#0d5cc7] hover:shadow-lg hover:shadow-blue-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search
                            </button>
                            <a href="{{ route('vat-report.export', request()->query()) }}"
                                class="bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- VAT Sales Table -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-[#1d7cf2] to-[#0d5cc7]">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Order #</th>
                                <th class="px-5 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Customer</th>
                                <th class="px-5 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">VAT Number</th>
                                <th class="px-5 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Order Type</th>
                                <th class="px-5 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">Subtotal</th>
                                <th class="px-5 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">VAT</th>
                                <th class="px-5 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">SSCL</th>
                                <th class="px-5 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">Total</th>
                                <th class="px-5 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Payment</th>
                                <th class="px-5 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Date/Time</th>
                                <th class="px-5 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($orders as $order)
                            @php
                                $cashAmount    = $order->payment->cash_amount   ?? 0;
                                $cardAmount    = $order->payment->card_amount   ?? 0;
                                $creditAmount  = $order->payment->credit_amount ?? 0;
                                $changeAmount  = $order->payment->change_amount ?? 0;
                                $paymentMethod = $order->payment ? $order->payment->payment_method : null;
                                $displayCash   = max(0, $cashAmount - $changeAmount);
                            @endphp
                            <tr class="hover:bg-blue-50 transition-colors" data-order-id="{{ $order->id }}">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-blue-600 font-mono font-semibold">{{ $order->order_number }}</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-800 font-medium">
                                    {{ $order->customer_name ?? '—' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="vat-badge">{{ $order->customer_vat_number }}</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="badge-status
                                        @if($order->order_type == 'dine_in')   bg-blue-600/20 text-blue-600
                                        @elseif($order->order_type == 'takeaway') bg-green-600/20 text-green-600
                                        @elseif($order->order_type == 'delivery') bg-purple-600/20 text-purple-600
                                        @elseif($order->order_type == 'pickme')   bg-orange-600/20 text-orange-600
                                        @else bg-gray-200 text-gray-600
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $order->order_type)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-gray-700">
                                    LKR {{ number_format($order->subtotal ?? 0, 2) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-semibold text-blue-700">
                                    LKR {{ number_format($order->vat_amount ?? 0, 2) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-semibold text-indigo-700">
                                    LKR {{ number_format($order->sscl_amount ?? 0, 2) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-semibold text-gray-800">
                                    LKR {{ number_format($order->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($order->payment)
                                        @php
                                            $isMixed = $paymentMethod === 'mixed' && $cashAmount > 0 && $cardAmount > 0;
                                        @endphp
                                        @if($paymentMethod === 'cash')
                                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-green-100 text-green-700">Cash</span>
                                        @elseif($paymentMethod === 'card')
                                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-blue-100 text-blue-700">Card</span>
                                        @elseif($isMixed)
                                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-purple-100 text-purple-700">Cash & Card</span>
                                        @else
                                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-gray-100 text-gray-700">{{ ucfirst($paymentMethod) }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-gray-600 text-sm">
                                    {{ $order->completed_at ? $order->completed_at->format('M d, Y H:i') : 'N/A' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button
                                            class="btn-action bg-gradient-to-r from-[#1d7cf2] to-[#0d5cc7] hover:shadow-lg hover:shadow-blue-500/50 text-white view-details-btn"
                                            data-order-id="{{ $order->id }}"
                                            title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            class="btn-action bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white print-receipt-btn"
                                            data-order-id="{{ $order->id }}"
                                            data-receipt-url="{{ route('sales-report.receipt', $order) }}"
                                            title="Print Receipt">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                        </svg>
                                        <p class="text-gray-500 text-lg">No VAT orders found for the selected filters</p>
                                        <p class="text-gray-400 text-sm mt-1">Only orders with a VAT customer assigned are shown here</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-300">
                            <tr>
                                <td colspan="4" class="px-5 py-4 text-right uppercase tracking-wider">Grand Total</td>
                                <td class="px-5 py-4 text-right">LKR {{ number_format($totals->total_subtotal, 2) }}</td>
                                <td class="px-5 py-4 text-right text-blue-700">LKR {{ number_format($totals->total_vat, 2) }}</td>
                                <td class="px-5 py-4 text-right text-indigo-700">LKR {{ number_format($totals->total_sscl, 2) }}</td>
                                <td class="px-5 py-4 text-right">LKR {{ number_format($totals->total_amount, 2) }}</td>
                                <td class="px-5 py-4"></td>
                                <td class="px-5 py-4"></td>
                                <td class="px-5 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                @if($orders->hasPages())
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    {{ $orders->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- Sale Details Modal -->
        <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="saleDetailsModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop fixed inset-0"></div>
                <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 max-w-4xl w-full">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
                        <h3 class="text-xl font-bold text-blue-700">VAT Sale Details</h3>
                        <button class="text-gray-500 hover:text-gray-700 close-modal">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        <!-- Sale Information -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-sm text-gray-500">Order Number</p>
                                <p class="text-base font-semibold text-gray-800" id="modal-order-number">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Payment Number</p>
                                <p class="text-base font-semibold text-gray-800" id="modal-payment-number">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Customer Name</p>
                                <p class="text-base font-semibold text-gray-800" id="modal-customer-name">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">VAT Number</p>
                                <p class="text-base font-semibold text-blue-700" id="modal-vat-number">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Waiter</p>
                                <p class="text-base font-semibold text-gray-800" id="modal-waiter">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Order Type</p>
                                <p class="text-base font-semibold text-gray-800" id="modal-order-type">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Date & Time</p>
                                <p class="text-base font-semibold text-gray-800" id="modal-date">-</p>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <h4 class="text-lg font-bold text-gray-800 mb-3">Order Items</h4>
                        <div class="bg-gray-50 rounded-lg overflow-hidden mb-6">
                            <table class="w-full" id="modal-items-table">
                                <thead class="bg-gradient-to-r from-[#1d7cf2] to-[#0d5cc7]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Item</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Qty</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase">Price</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-items-body" class="divide-y divide-gray-200"></tbody>
                            </table>
                        </div>

                        <!-- Payment Summary -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="text-lg font-bold text-gray-800 mb-3">Payment Summary</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="text-gray-800 font-semibold" id="modal-subtotal">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">VAT Amount:</span>
                                    <span class="text-blue-700 font-semibold" id="modal-vat">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SSCL Amount:</span>
                                    <span class="text-indigo-700 font-semibold" id="modal-sscl">-</span>
                                </div>
                                <div class="flex justify-between border-t border-blue-200 pt-2 mt-2">
                                    <span class="text-gray-800 font-bold">Total:</span>
                                    <span class="text-blue-700 font-bold text-lg" id="modal-total">-</span>
                                </div>
                                <div class="flex justify-between border-t border-blue-200 pt-2 mt-2">
                                    <span class="text-gray-600">Cash Payment:</span>
                                    <span class="text-green-600 font-semibold" id="modal-cash">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Card Payment:</span>
                                    <span class="text-blue-600 font-semibold" id="modal-card">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Credit Payment:</span>
                                    <span class="text-orange-600 font-semibold" id="modal-credit">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Change:</span>
                                    <span class="text-gray-800 font-semibold" id="modal-change">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <button class="bg-white hover:bg-gray-100 text-gray-700 font-semibold py-2 px-6 rounded-lg transition close-modal border border-gray-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function () {
                $.ajaxSetup({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                });

                // View Details
                $('.view-details-btn').on('click', function () {
                    const orderId = $(this).data('order-id');

                    $.get(`/sales-report/sale-details/${orderId}`)
                        .done(function (response) {
                            const order = response.order;
                            const items = response.items;

                            $('#modal-order-number').text(order.order_number);
                            $('#modal-payment-number').text(order.payment_number);
                            $('#modal-customer-name').text(order.customer_name || '—');
                            $('#modal-vat-number').text(order.customer_vat_number || 'Not Eligible');
                            $('#modal-waiter').text(order.waiter_name);
                            $('#modal-order-type').text(order.order_type);
                            $('#modal-date').text(order.completed_at);

                            $('#modal-subtotal').text('LKR ' + parseFloat(order.subtotal).toFixed(2));
                            $('#modal-vat').text('LKR ' + parseFloat(order.vat_amount || 0).toFixed(2));
                            $('#modal-sscl').text('LKR ' + parseFloat(order.sscl_amount || 0).toFixed(2));
                            $('#modal-total').text('LKR ' + parseFloat(order.total_amount).toFixed(2));
                            $('#modal-cash').text('LKR ' + parseFloat(order.cash_amount).toFixed(2));
                            $('#modal-card').text('LKR ' + parseFloat(order.card_amount).toFixed(2));
                            $('#modal-credit').text('LKR ' + parseFloat(order.credit_amount).toFixed(2));
                            $('#modal-change').text('LKR ' + parseFloat(order.change_amount).toFixed(2));

                            let itemsHtml = '';
                            items.forEach(function (item) {
                                itemsHtml += `
                                    <tr class="bg-white hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-800">
                                            ${item.item_name}
                                            ${item.modifiers ? '<br><span class="text-xs text-gray-500">' + item.modifiers + '</span>' : ''}
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-800">${item.quantity}</td>
                                        <td class="px-4 py-3 text-right text-gray-800">LKR ${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td class="px-4 py-3 text-right text-gray-800 font-semibold">LKR ${parseFloat(item.subtotal).toFixed(2)}</td>
                                    </tr>`;
                            });
                            $('#modal-items-body').html(itemsHtml);
                            $('#saleDetailsModal').removeClass('hidden');
                        })
                        .fail(function () { alert('Failed to load order details'); });
                });

                // Print Receipt Button - Using Hidden Iframe
                $('.print-receipt-btn').on('click', function (e) {
                    e.preventDefault();
                    const btn = $(this);
                    const orderId = btn.data('order-id');

                    btn.prop('disabled', true);
                    const originalHtml = btn.html();
                    btn.html('<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>');

                    let iframe = document.getElementById('print-iframe');
                    if (!iframe) {
                        iframe = document.createElement('iframe');
                        iframe.id = 'print-iframe';
                        iframe.style.display = 'none';
                        document.body.appendChild(iframe);
                    }

                    iframe.onload = function() {
                        try {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        } catch (err) {
                            console.error('Print Error:', err);
                            alert('Print failed: ' + err.message);
                        } finally {
                            btn.html(originalHtml);
                            btn.prop('disabled', false);
                        }
                    };

                    iframe.src = `/sales-report/receipt/${orderId}`;
                });

                // Close modal
                $('.close-modal, .modal-backdrop').on('click', function (e) {
                    if (e.target === this || $(this).hasClass('close-modal')) {
                        $('#saleDetailsModal').addClass('hidden');
                    }
                });
            });
        </script>
        @endpush
    </div>
</div>
@endsection
