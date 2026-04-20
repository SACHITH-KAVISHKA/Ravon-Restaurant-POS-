@extends('layouts.app')

@section('title', 'Item Wise Summary - Ravon Restaurant POS')

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
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">Item Wise Summary</h1>
                    <p class="text-gray-600">View item-wise sales performance and transaction details</p>
                </div>

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form id="filterForm" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Category Filter -->
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Category
                                </label>
                                <select id="category_id" name="category_id"
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date
                                </label>
                                <input type="date" id="start_date" name="start_date" value="{{ date('Y-m-d') }}"
                                    max="{{ date('Y-m-d') }}"
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    End Date
                                </label>
                                <input type="date" id="end_date" name="end_date" value="{{ date('Y-m-d') }}"
                                    max="{{ date('Y-m-d') }}"
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Actions -->
                            <div class="flex items-end space-x-2">
                                <button type="submit"
                                    class="flex-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    Search
                                </button>
                                <button type="button" id="exportSummaryBtn"
                                    class="bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Export
                                </button>
                            </div>
                        </div>
                    </form>
                </div>



                <!-- Data Table Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="table-responsive">
                        <table class="w-full" id="salesTable">
                            <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <tr>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">#</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Item Code</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Total Qty</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="salesTableBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                                            <p class="text-gray-600">Loading data...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Details Modal -->
    <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="itemDetailsModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="modal-backdrop fixed inset-0"></div>
            <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 max-w-6xl w-full">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-purple-100">
                    <h3 class="text-xl font-bold text-purple-700">Item Transaction Details</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Item</p>
                            <p class="text-lg font-semibold text-gray-800"><span id="modalItemName" class="text-purple-700"></span></p>
                        </div>
                        <button type="button" id="exportDetailsBtn"
                            class="bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center whitespace-nowrap">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Details
                        </button>
                    </div>

                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <tr>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Order #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Date & Time</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase">Unit Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="detailsTableBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="6" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600 mb-3"></div>
                                            <p class="text-gray-600">Loading...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-purple-50 border-t border-purple-200">
                                <tr>
                                    <td colspan="3" class="text-right py-3 px-4 text-gray-800 font-bold uppercase tracking-wider">Total</td>
                                    <td class="text-center py-3 px-4 text-gray-800 font-bold" id="modalTotalQty">0</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button type="button" class="bg-white hover:bg-gray-100 text-gray-700 font-semibold py-2 px-6 rounded-lg transition border border-gray-300" onclick="closeModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        console.log('=== SCRIPT LOADING ===');
        console.log('jQuery loaded:', typeof $ !== 'undefined');
        console.log('Document ready state:', document.readyState);

        let currentItemId = null;
        let currentModifierId = null;

        $(document).ready(function () {
            console.log('=== DOCUMENT READY ===');
            console.log('Start date value:', $('#start_date').val());
            console.log('End date value:', $('#end_date').val());

            // Load initial data
            console.log('Calling loadSalesData()...');
            loadSalesData();

            // Filter form submission
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                console.log('Filter form submitted');
                loadSalesData();
            });

            // Export summary button
            $('#exportSummaryBtn').on('click', function () {
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                const categoryId = $('#category_id').val();

                const params = new URLSearchParams({
                    start_date: startDate,
                    end_date: endDate
                });

                if (categoryId) {
                    params.append('category_id', categoryId);
                }

                window.location.href = `{{ route('reports.item-sales.export') }}?${params.toString()}`;
            });

            // Export details button in modal
            $('#exportDetailsBtn').on('click', function () {
                if (!currentItemId) {
                    alert('Invalid item selection');
                    return;
                }

                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();

                const params = new URLSearchParams({
                    item_id: currentItemId,
                    start_date: startDate,
                    end_date: endDate
                });

                // Add modifier_id if present
                if (currentModifierId) {
                    params.append('item_modifier_id', currentModifierId);
                }

                window.location.href = `{{ route('reports.item-sales.export-details') }}?${params.toString()}`;
            });

            $('.modal-backdrop').on('click', function (e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });

        function loadSalesData() {
            console.log('=== LOAD SALES DATA CALLED ===');
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            const categoryId = $('#category_id').val();

            console.log('Date range:', startDate, 'to', endDate, 'Category:', categoryId);
            console.log('AJAX URL:', '{{ route("reports.item-sales.filter") }}');
            console.log('CSRF Token:', '{{ csrf_token() }}');

            $.ajax({
                url: '{{ route("reports.item-sales.filter") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    start_date: startDate,
                    end_date: endDate,
                    category_id: categoryId
                },
                beforeSend: function () {
                    console.log('AJAX request starting...');
                    $('#salesTableBody').html(`
                            <tr>
                                <td colspan="5" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                                        <p class="text-gray-600">Loading data...</p>
                                    </div>
                                </td>
                            </tr>
                        `);
                },
                success: function (response) {
                    console.log('=== AJAX SUCCESS ===');
                    console.log('Response:', response);
                    console.log('Summary:', response.summary);
                    console.log('Data items:', response.data);
                    updateStatsCards(response.summary, startDate, endDate);
                    renderTable(response.data);
                },
                error: function (xhr) {
                    console.error('=== AJAX ERROR ===');
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseJSON || xhr.responseText);
                    console.error('Full XHR:', xhr);
                    $('#salesTableBody').html(`
                            <tr>
                                <td colspan="5" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-gray-600">Error loading data. Please try again.</p>
                                        <p class="text-red-600 text-sm mt-2">Status: ${xhr.status}</p>
                                    </div>
                                </td>
                            </tr>
                        `);
                }
            });
        }

        function updateStatsCards(summary, startDate, endDate) {
            $('#totalQuantity').text(summary.total_quantity.toLocaleString());
            $('#uniqueItems').text(summary.unique_items.toLocaleString());
            $('#periodDisplay').text(startDate + ' to ' + endDate);
        }

        function renderTable(data) {
            console.log('Rendering table with data:', data);
            // Build table body
            let bodyHtml = '';

            if (data.length === 0) {
                bodyHtml = `
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="text-gray-600">No sales data found for the selected period.</p>
                                </div>
                            </td>
                        </tr>
                    `;
            } else {
                data.forEach((item, index) => {
                    const modifierId = item.item_modifier_id || 'null';
                                bodyHtml += `
                            <tr class="hover:bg-purple-50 transition-colors">
                                <td class="px-6 py-4 text-center text-gray-600">${index + 1}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">${item.item_code}</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="text-gray-800 font-semibold">${item.item_name}</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center font-semibold text-purple-600">${item.total_quantity}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button type="button" class="btn-action bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white view-item-btn"
                                            title="View Details"
                                            onclick="showItemDetails(${item.item_id}, ${modifierId}, '${item.item_name.replace(/'/g, "\\'")}')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        `;
                });
            }

            $('#salesTableBody').html(bodyHtml);
        }

        function showItemDetails(itemId, modifierId, itemName) {
            currentItemId = itemId;
            currentModifierId = modifierId;

            $('#modalItemName').text(itemName);

            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            const requestData = {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                start_date: startDate,
                end_date: endDate
            };

            // Add modifier_id if present
            if (modifierId && modifierId !== null) {
                requestData.item_modifier_id = modifierId;
            }

            $.ajax({
                url: '{{ route("reports.item-sales.details") }}',
                type: 'POST',
                data: requestData,
                beforeSend: function () {
                    $('#detailsTableBody').html(`
                            <tr>
                                <td colspan="6" class="text-center py-8">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600 mb-3"></div>
                                        <p class="text-gray-600">Loading...</p>
                                    </div>
                                </td>
                            </tr>
                        `);
                },
                success: function (response) {
                    console.log('Item details loaded:', response);
                    renderDetailsTable(response.transactions);
                    $('#modalTotalQty').text(response.total_quantity);
                    $('#itemDetailsModal').removeClass('hidden');
                },
                error: function (xhr) {
                    console.error('Error loading item details:', xhr.responseJSON || xhr.responseText);
                    alert('Error loading transaction details');
                    console.error('Error:', xhr);
                }
            });
        }

        function closeModal() {
            $('#itemDetailsModal').addClass('hidden');
        }

        function renderDetailsTable(transactions) {
            let html = '';

            if (transactions.length === 0) {
                html = `
                        <tr>
                            <td colspan="6" class="text-center py-8">
                                <p class="text-gray-600">No transaction details found.</p>
                            </td>
                        </tr>
                    `;
            } else {
                transactions.forEach((row, index) => {
                    html += `
                            <tr class="hover:bg-purple-50 transition-colors bg-white">
                                <td class="px-4 py-3 text-center text-gray-600">${index + 1}</td>
                                <td class="px-4 py-3"><span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">${row.order_number}</span></td>
                                <td class="px-4 py-3 text-gray-600">${row.completed_at}</td>
                                <td class="px-4 py-3 text-center text-purple-600 font-semibold">${row.quantity}</td>
                                <td class="px-4 py-3 text-right text-gray-600">LKR ${parseFloat(row.unit_price).toFixed(2)}</td>
                                <td class="px-4 py-3 text-right text-gray-800 font-semibold">LKR ${parseFloat(row.subtotal).toFixed(2)}</td>
                            </tr>
                        `;
                });
            }

            $('#detailsTableBody').html(html);
        }
    </script>
@endpush