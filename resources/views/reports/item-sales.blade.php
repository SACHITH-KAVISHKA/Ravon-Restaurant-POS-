@extends('layouts.app')

@section('title', 'Item Wise Summary - Ravon Restaurant POS')

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .stat-card {
        transition: transform 0.2s;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
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
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">Sales by Item Summary</h1>
                <p class="text-gray-600">View item-wise sales performance and transaction details</p>
            </div>

            <!-- Filter Card -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                <form id="filterForm" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-600 mb-2">
                                Start Date
                            </label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="{{ date('Y-m-d') }}"
                                max="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                style="color-scheme: dark;">
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-600 mb-2">
                                End Date
                            </label>
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="{{ date('Y-m-d') }}"
                                max="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                style="color-scheme: dark;">
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end space-x-2">
                            <button
                                type="submit"
                                class="flex-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Apply Filter
                            </button>
                            <button
                                type="button"
                                id="exportSummaryBtn"
                                class="bg-green-600 hover:bg-green-600/90 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                            </button>
                        </div>
                    </div>
                </form>
            </div>



            <!-- Data Table Card -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Item Sales Details</h2>
                    <div class="table-responsive">
                        <table class="w-full" id="salesTable">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                    <th class="text-center py-3 px-4 text-white font-semibold">#</th>
                                    <th class="text-left py-3 px-4 text-white font-semibold">Item Code</th>
                                    <th class="text-left py-3 px-4 text-white font-semibold">Item Name</th>
                                    <th class="text-center py-3 px-4 text-white font-semibold">Total Qty</th>
                                    <th class="text-center py-3 px-4 text-white font-semibold">Actions</th>
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
</div>

<!-- Item Details Modal -->
<div id="itemDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full border border-gray-300">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-[#667eea] to-[#764ba2] px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Item Transaction Details
                    </h3>
                    <button type="button" class="text-white hover:text-white/80 transition" onclick="closeModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <div class="mb-4">
                    <h6 class="text-lg font-semibold text-gray-800">Item: <span id="modalItemName" class="text-purple-600"></span></h6>
                </div>

                <div class="flex justify-end mb-4">
                    <button type="button" id="exportDetailsBtn" class="bg-green-600 hover:bg-green-600/90 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Details
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <th class="text-center py-3 px-4 text-white font-semibold">#</th>
                                <th class="text-left py-3 px-4 text-white font-semibold">Order #</th>
                                <th class="text-left py-3 px-4 text-white font-semibold">Date & Time</th>
                                <th class="text-center py-3 px-4 text-white font-semibold">Qty</th>
                                <th class="text-right py-3 px-4 text-white font-semibold">Unit Price</th>
                                <th class="text-right py-3 px-4 text-white font-semibold">Subtotal</th>
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
                        <tfoot class="border-t-2 border-purple-600/50">
                            <tr class="bg-purple-50">
                                <td colspan="3" class="text-right py-3 px-4 text-gray-800 font-bold">TOTAL:</td>
                                <td class="text-center py-3 px-4 text-gray-800 font-bold" id="modalTotalQty">0</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
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

    $(document).ready(function() {
        console.log('=== DOCUMENT READY ===');
        console.log('Start date value:', $('#start_date').val());
        console.log('End date value:', $('#end_date').val());
        
        // Load initial data
        console.log('Calling loadSalesData()...');
        loadSalesData();

        // Filter form submission
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Filter form submitted');
            loadSalesData();
        });

        // Export summary button
        $('#exportSummaryBtn').on('click', function() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate
            });
            
            window.location.href = `{{ route('reports.item-sales.export') }}?${params.toString()}`;
        });

        // Export details button in modal
        $('#exportDetailsBtn').on('click', function() {
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
            
            window.location.href = `{{ route('reports.item-sales.export-details') }}?${params.toString()}`;
        });
    });

    function loadSalesData() {
        console.log('=== LOAD SALES DATA CALLED ===');
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        
        console.log('Date range:', startDate, 'to', endDate);
        console.log('AJAX URL:', '{{ route("reports.item-sales.filter") }}');
        console.log('CSRF Token:', '{{ csrf_token() }}');

        $.ajax({
            url: '{{ route("reports.item-sales.filter") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                start_date: startDate,
                end_date: endDate
            },
            beforeSend: function() {
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
            success: function(response) {
                console.log('=== AJAX SUCCESS ===');
                console.log('Response:', response);
                console.log('Summary:', response.summary);
                console.log('Data items:', response.data);
                updateStatsCards(response.summary, startDate, endDate);
                renderTable(response.data);
            },
            error: function(xhr) {
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
                bodyHtml += `
                    <tr class="hover:bg-purple-50 transition">
                        <td class="text-center py-3 px-4 text-gray-600">${index + 1}</td>
                        <td class="py-3 px-4 text-gray-600">${item.item_code}</td>
                        <td class="py-3 px-4"><span class="text-gray-800 font-semibold">${item.item_name}</span></td>
                        <td class="text-center py-3 px-4"><span class="text-purple-600 font-bold">${item.total_quantity}</span></td>
                        <td class="text-center py-3 px-4">
                            <button class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center mx-auto" 
                                    onclick="showItemDetails(${item.item_id}, '${item.item_name}')">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View
                            </button>
                        </td>
                    </tr>
                `;
            });
        }

        $('#salesTableBody').html(bodyHtml);
    }

    function showItemDetails(itemId, itemName) {
        currentItemId = itemId;

        $('#modalItemName').text(itemName);

        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        $.ajax({
            url: '{{ route("reports.item-sales.details") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                start_date: startDate,
                end_date: endDate
            },
            beforeSend: function() {
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
            success: function(response) {
                console.log('Item details loaded:', response);
                renderDetailsTable(response.transactions);
                $('#modalTotalQty').text(response.total_quantity);
                $('#itemDetailsModal').removeClass('hidden');
            },
            error: function(xhr) {
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
                    <tr class="hover:bg-purple-50 transition">
                        <td class="text-center py-3 px-4 text-gray-600">${index + 1}</td>
                        <td class="py-3 px-4"><span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">${row.order_number}</span></td>
                        <td class="py-3 px-4 text-gray-600">${row.completed_at}</td>
                        <td class="text-center py-3 px-4 text-purple-600 font-semibold">${row.quantity}</td>
                        <td class="text-right py-3 px-4 text-gray-600">Rs. ${parseFloat(row.unit_price).toFixed(2)}</td>
                        <td class="text-right py-3 px-4 text-gray-800 font-semibold">Rs. ${parseFloat(row.subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        $('#detailsTableBody').html(html);
    }
</script>
@endpush



