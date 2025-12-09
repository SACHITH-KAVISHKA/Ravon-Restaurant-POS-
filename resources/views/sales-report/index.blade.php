@extends('layouts.app')

@section('title', 'Sales Report - Ravon Restaurant POS')

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
    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto">
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Sales Report</h1>
                <p class="text-gray-400">View and manage completed sales transactions</p>
            </div>

            <!-- Filter Card -->
            <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-xl shadow-xl p-6 mb-6">
                <form method="GET" action="{{ route('sales-report.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-300 mb-2">
                                Start Date
                            </label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="{{ $startDate }}"
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-300 mb-2">
                                End Date
                            </label>
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="{{ $endDate }}"
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Order Type -->
                        <div>
                            <label for="order_type" class="block text-sm font-medium text-gray-300 mb-2">
                                Order Type
                            </label>
                            <select
                                id="order_type"
                                name="order_type"
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Types</option>
                                <option value="dine_in" {{ $orderType == 'dine_in' ? 'selected' : '' }}>Dine In</option>
                                <option value="takeaway" {{ $orderType == 'takeaway' ? 'selected' : '' }}>Takeaway</option>
                                <option value="delivery" {{ $orderType == 'delivery' ? 'selected' : '' }}>Delivery</option>
                                <option value="uber_eats" {{ $orderType == 'uber_eats' ? 'selected' : '' }}>Uber Eats</option>
                                <option value="pickme" {{ $orderType == 'pickme' ? 'selected' : '' }}>PickMe</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end space-x-2">
                            <button
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search
                            </button>
                            <a
                                href="{{ route('sales-report.export', request()->query()) }}"
                                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export
                            </a>
                        </div>
                    </div>
                </form>
            </div>


            <!-- Sales Table -->
            <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-xl shadow-xl overflow-hidden">
                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="bg-gray-900">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Order #</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Order Type</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Payment</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Cash</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Card</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">Credit</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Date/Time</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @forelse($orders as $order)
                            @php
                            // Get payment amounts from specific columns
                            $cashAmount = $order->payment->cash_amount ?? 0;
                            $cardAmount = $order->payment->card_amount ?? 0;
                            $creditAmount = $order->payment->credit_amount ?? 0;
                            $changeAmount = $order->payment->change_amount ?? 0;
                            $paymentMethod = $order->payment ? $order->payment->payment_method : null;
                            
                            // Subtract change from cash amount only (change is given back, so net cash received is less)
                            $displayCashAmount = max(0, $cashAmount - $changeAmount);
                            @endphp
                            <tr class="hover:bg-gray-700/50 transition-colors" data-order-id="{{ $order->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-blue-400 font-mono font-semibold">{{ $order->order_number }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="badge-status 
                                    @if($order->order_type == 'dine_in') bg-blue-600 text-blue-100
                                    @elseif($order->order_type == 'takeaway') bg-green-600 text-green-100
                                    @elseif($order->order_type == 'delivery') bg-purple-600 text-purple-100
                                    @elseif($order->order_type == 'uber_eats') bg-yellow-600 text-yellow-100
                                    @elseif($order->order_type == 'pickme') bg-orange-600 text-orange-100
                                    @endif">
                                        {{ ucfirst(str_replace('_', ' ', $order->order_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-white">
                                    LKR {{ number_format($order->total_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($order->payment)
                                    @php
                                    $paymentMethod = $order->payment->payment_method;
                                    $isMixed = $paymentMethod === 'mixed' && $cashAmount > 0 && $cardAmount > 0;
                                    @endphp

                                    @if($paymentMethod === 'cash')
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-cyan-500 text-white">Cash</span>
                                    @elseif($paymentMethod === 'card')
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-cyan-500 text-white">Card</span>
                                    @elseif($isMixed)
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-cyan-500 text-white">Cash and Card</span>
                                    @else
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-md bg-cyan-500 text-white">{{ ucfirst($paymentMethod) }}</span>
                                    @endif
                                    @else
                                    <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-300">
                                    LKR {{ number_format($displayCashAmount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-300">
                                    LKR {{ number_format($cardAmount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-300">
                                    LKR {{ number_format($creditAmount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-sm">
                                    {{ $order->completed_at ? $order->completed_at->format('M d, Y H:i') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button
                                            class="btn-action bg-blue-600 hover:bg-blue-700 text-white view-details-btn"
                                            data-order-id="{{ $order->id }}"
                                            title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            class="btn-action bg-green-600 hover:bg-green-700 text-white print-receipt-btn"
                                            data-receipt-url="{{ route('sales-report.receipt', $order) }}"
                                            title="Print Receipt">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                        <button
                                            class="btn-action bg-red-600 hover:bg-red-700 text-white delete-order-btn"
                                            data-order-id="{{ $order->id }}"
                                            data-order-number="{{ $order->order_number }}"
                                            title="Delete Order">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-gray-400 text-lg">No sales records found for the selected filters</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($orders->hasPages())
                <div class="bg-gray-900 px-6 py-4 border-t border-gray-700">
                    {{ $orders->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- Sale Details Modal -->
        <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="saleDetailsModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop fixed inset-0"></div>
                <div class="relative bg-gray-800 rounded-xl shadow-2xl border border-gray-700 max-w-4xl w-full">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                        <h3 class="text-xl font-bold text-white">Sale Details</h3>
                        <button class="text-gray-400 hover:text-white close-modal">
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
                                <p class="text-sm text-gray-400">Order Number</p>
                                <p class="text-lg font-semibold text-white" id="modal-order-number">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Payment Number</p>
                                <p class="text-lg font-semibold text-white" id="modal-payment-number">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Waiter</p>
                                <p class="text-lg font-semibold text-white" id="modal-waiter">-</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-400">Order Type</p>
                                <p class="text-lg font-semibold text-white" id="modal-order-type">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Date & Time</p>
                                <p class="text-lg font-semibold text-white" id="modal-date">-</p>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <h4 class="text-lg font-bold text-white mb-3">Order Items</h4>
                        <div class="bg-gray-900 rounded-lg overflow-hidden mb-6">
                            <table class="w-full" id="modal-items-table">
                                <thead class="bg-gray-950">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Item</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase">Qty</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase">Price</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-items-body" class="divide-y divide-gray-800">
                                    <!-- Items will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Payment Summary -->
                        <div class="bg-gray-900 rounded-lg p-4">
                            <h4 class="text-lg font-bold text-white mb-3">Payment Summary</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Subtotal:</span>
                                    <span class="text-white font-semibold" id="modal-subtotal">-</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-700 pt-2 mt-2">
                                    <span class="text-white font-bold">Total:</span>
                                    <span class="text-white font-bold text-lg" id="modal-total">-</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-700 pt-2 mt-2">
                                    <span class="text-gray-400">Cash Payment:</span>
                                    <span class="text-green-400 font-semibold" id="modal-cash">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Card Payment:</span>
                                    <span class="text-purple-400 font-semibold" id="modal-card">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Credit Payment:</span>
                                    <span class="text-orange-400 font-semibold" id="modal-credit">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Change:</span>
                                    <span class="text-white font-semibold" id="modal-change">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end px-6 py-4 border-t border-gray-700">
                        <button class="bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-lg transition close-modal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="deleteConfirmModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop fixed inset-0"></div>
                <div class="relative bg-gray-800 rounded-xl shadow-2xl border border-gray-700 max-w-md w-full">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                        <h3 class="text-xl font-bold text-white">Confirm Delete</h3>
                        <button class="text-gray-400 hover:text-white close-delete-modal">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4">
                        <div class="flex items-center mb-4">
                            <div class="bg-red-600/20 p-3 rounded-full mr-4">
                                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white mb-1">Delete Order</h4>
                                <p class="text-gray-400 text-sm">Are you sure you want to delete order <span class="text-white font-semibold" id="delete-order-number">-</span>?</p>
                            </div>
                        </div>
                        <p class="text-gray-400 text-sm">This action will mark the order as deleted. This cannot be undone.</p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-700">
                        <button class="bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-lg transition close-delete-modal">
                            Cancel
                        </button>
                        <button id="confirm-delete-btn" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                // CSRF Token setup
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                let currentOrderId = null;

                // View Details Button
                $('.view-details-btn').on('click', function() {
                    const orderId = $(this).data('order-id');

                    $.get(`/sales-report/sale-details/${orderId}`)
                        .done(function(response) {
                            const order = response.order;
                            const items = response.items;

                            // Populate order info
                            $('#modal-order-number').text(order.order_number);
                            $('#modal-payment-number').text(order.payment_number);
                            $('#modal-waiter').text(order.waiter_name);
                            $('#modal-order-type').text(order.order_type);
                            $('#modal-date').text(order.completed_at);

                            // Populate payment summary
                            $('#modal-subtotal').text('LKR ' + parseFloat(order.subtotal).toFixed(2));
                            $('#modal-total').text('LKR ' + parseFloat(order.total_amount).toFixed(2));
                            $('#modal-cash').text('LKR ' + parseFloat(order.cash_amount).toFixed(2));
                            $('#modal-card').text('LKR ' + parseFloat(order.card_amount).toFixed(2));
                            $('#modal-credit').text('LKR ' + parseFloat(order.credit_amount).toFixed(2));
                            $('#modal-change').text('LKR ' + parseFloat(order.change_amount).toFixed(2));

                            // Populate items table
                            let itemsHtml = '';
                            items.forEach(function(item) {
                                itemsHtml += `
                        <tr>
                            <td class="px-4 py-3 text-white">
                                ${item.item_name}
                                ${item.modifiers ? '<br><span class="text-xs text-gray-400">' + item.modifiers + '</span>' : ''}
                            </td>
                            <td class="px-4 py-3 text-center text-white">${item.quantity}</td>
                            <td class="px-4 py-3 text-right text-white">LKR ${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td class="px-4 py-3 text-right text-white font-semibold">LKR ${parseFloat(item.subtotal).toFixed(2)}</td>
                        </tr>
                    `;
                            });
                            $('#modal-items-body').html(itemsHtml);

                            // Show modal
                            $('#saleDetailsModal').removeClass('hidden');
                        })
                        .fail(function() {
                            alert('Failed to load order details');
                        });
                });

                // Delete Order Button
                $('.delete-order-btn').on('click', function() {
                    currentOrderId = $(this).data('order-id');
                    const orderNumber = $(this).data('order-number');

                    $('#delete-order-number').text(orderNumber);
                    $('#deleteConfirmModal').removeClass('hidden');
                });

                // Confirm Delete
                $('#confirm-delete-btn').on('click', function() {
                    if (!currentOrderId) return;

                    $.ajax({
                        url: `/sales-report/order/${currentOrderId}`,
                        method: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                // Remove row with animation
                                $(`tr[data-order-id="${currentOrderId}"]`).fadeOut(300, function() {
                                    $(this).remove();

                                    // Check if table is empty
                                    if ($('tbody tr').length === 0) {
                                        location.reload();
                                    }
                                });

                                $('#deleteConfirmModal').addClass('hidden');

                                // Show success message
                                showNotification('Order deleted successfully', 'success');
                            } else {
                                alert('Failed to delete order: ' + response.message);
                            }
                        },
                        error: function(xhr) {
                            alert('Error deleting order: ' + (xhr.responseJSON?.message || 'Unknown error'));
                        }
                    });
                });

                // Close modals
                $('.close-modal').on('click', function(e) {
                    e.preventDefault();
                    $('#saleDetailsModal').addClass('hidden');
                });

                $('.modal-backdrop').on('click', function(e) {
                    if (e.target === this) {
                        $('#saleDetailsModal').addClass('hidden');
                        $('#deleteConfirmModal').addClass('hidden');
                    }
                });

                $('.close-delete-modal').on('click', function() {
                    $('#deleteConfirmModal').addClass('hidden');
                    currentOrderId = null;
                });

                // Print Receipt Button
                $('.print-receipt-btn').on('click', function(e) {
                    e.preventDefault();
                    const receiptUrl = $(this).data('receipt-url');

                    // Create hidden iframe for printing
                    let printFrame = $('<iframe>', {
                        name: 'print_frame',
                        style: 'position:absolute;top:-1000px;left:-1000px;'
                    });

                    $('body').append(printFrame);

                    // Load receipt content and trigger print
                    printFrame.on('load', function() {
                        try {
                            this.contentWindow.focus();
                            this.contentWindow.print();

                            // Remove iframe after printing
                            setTimeout(() => {
                                printFrame.remove();
                            }, 1000);
                        } catch (e) {
                            console.error('Print failed:', e);
                            printFrame.remove();
                        }
                    });

                    printFrame.attr('src', receiptUrl);
                });

                // Simple notification function
                function showNotification(message, type) {
                    const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
                    const notification = $(`
            <div class="fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50">
                ${message}
            </div>
        `);

                    $('body').append(notification);

                    setTimeout(() => {
                        notification.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
            });
        </script>
        @endpush
    </div>
</div>
@endsection