@extends('layouts.app')

@section('title', 'RM Sales Report - Ravon Restaurant POS')

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
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">
                        Raw Material Sales Report
                    </h1>
                    <p class="text-gray-600">Analyze raw material usage based on POS item sales over a selected period.</p>
                </div>

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form id="filterForm" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Raw Material -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="raw_material_id" class="block text-sm font-medium text-gray-600 mb-2">
                                    Raw Material
                                </label>
                                <select id="raw_material_id" name="raw_material_id" required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="">Select Raw Material</option>
                                    @foreach($rawMaterials as $rm)
                                        <option value="{{ $rm->id }}">{{ $rm->item_name }} ({{ $rm->item_code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-600 mb-2">
                                    Start Date
                                </label>
                                <input type="date" id="start_date" name="start_date" value="{{ date('Y-m-d') }}"
                                    max="{{ date('Y-m-d') }}" required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-600 mb-2">
                                    End Date
                                </label>
                                <input type="date" id="end_date" name="end_date" value="{{ date('Y-m-d') }}"
                                    max="{{ date('Y-m-d') }}" required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>

                        </div>
                        <div class="flex justify-end mt-4">
                            <button type="submit"
                                class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-8 rounded-lg transition duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Generate Report
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Summary Stats -->
                <div id="summaryStats" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 hidden">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 stat-card flex flex-col justify-center items-center">
                        <h3 class="text-gray-500 text-sm font-semibold mb-1">Raw Material</h3>
                        <p class="text-2xl font-bold text-gray-800" id="summaryRmName">-</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 stat-card flex flex-col justify-center items-center">
                        <h3 class="text-gray-500 text-sm font-semibold mb-1">Total Used In Sales Period</h3>
                        <p class="text-3xl font-bold text-purple-600"><span id="summaryTotalUsed">-</span> <span id="summaryUnit" class="text-lg text-gray-500"></span></p>
                    </div>
                </div>

                <!-- Data Table Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Sales Usage Details</h2>
                        <div class="table-responsive">
                            <table class="w-full" id="salesTable">
                                <thead>
                                    <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                        <th class="text-center py-3 px-4 text-white font-semibold rounded-tl-lg">#</th>
                                        <th class="text-left py-3 px-4 text-white font-semibold">POS Item Name</th>
                                        <th class="text-center py-3 px-4 text-white font-semibold">Recipe Qty / Unit</th>
                                        <th class="text-center py-3 px-4 text-white font-semibold">Item Qty Sold</th>
                                        <th class="text-center py-3 px-4 text-white font-semibold">Total RM Used</th>
                                        <th class="text-center py-3 px-4 text-white font-semibold rounded-tr-lg">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="salesTableBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="text-center py-12">
                                            <p class="text-gray-500 text-sm">Please select a raw material and date range to generate the report.</p>
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

    <!-- Order Details Modal -->
    <div id="itemDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full border border-gray-300">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-[#667eea] to-[#764ba2] px-6 py-4 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Order Transactions For Item
                        </h3>
                        <button type="button" class="text-white hover:text-white/80 transition" onclick="closeModal()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <div class="mb-4">
                        <h6 class="text-lg font-semibold text-gray-800">POS Item: <span id="modalItemName" class="text-purple-600"></span></h6>
                    </div>

                    <div class="table-responsive">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                    <th class="text-center py-3 px-4 text-white font-semibold rounded-tl-lg">#</th>
                                    <th class="text-left py-3 px-4 text-white font-semibold">Order #</th>
                                    <th class="text-left py-3 px-4 text-white font-semibold">Customer</th>
                                    <th class="text-left py-3 px-4 text-white font-semibold">Date & Time</th>
                                    <th class="text-center py-3 px-4 text-white font-semibold rounded-tr-lg">Qty Sold</th>
                                </tr>
                            </thead>
                            <tbody id="detailsTableBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600 mb-3"></div>
                                            <p class="text-gray-600">Loading...</p>
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

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentItemId = null;
        let currentModifierId = null;

        $(document).ready(function () {
            // Filter form submission
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                loadReportData();
            });
        });

        function loadReportData() {
            const rawMaterialId = $('#raw_material_id').val();
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            if (!rawMaterialId) {
                alert("Please select a raw material.");
                return;
            }

            $.ajax({
                url: '{{ route("reports.rm-sales.filter") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    raw_material_id: rawMaterialId,
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function () {
                    $('#summaryStats').addClass('hidden');
                    $('#salesTableBody').html(`
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                                    <p class="text-gray-600">Generating report...</p>
                                </div>
                            </td>
                        </tr>
                    `);
                },
                success: function (response) {
                    if (response.success) {
                        updateSummaryStats(response.summary);
                        renderTable(response.data);
                    }
                },
                error: function (xhr) {
                    console.error(xhr);
                    $('#salesTableBody').html(`
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-gray-600">Error loading data. Please try again.</p>
                                </div>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function updateSummaryStats(summary) {
            $('#summaryRmName').text(summary.raw_material_name);
            $('#summaryTotalUsed').text(parseFloat(summary.total_rm_used).toFixed(3));
            $('#summaryUnit').text(summary.unit);
            $('#summaryStats').removeClass('hidden');
        }

        function renderTable(data) {
            let bodyHtml = '';

            if (data.length === 0) {
                bodyHtml = `
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-gray-600">No sales transactions found for POS items associated with this raw material during the selected period.</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                data.forEach((item, index) => {
                    const modifierId = item.item_modifier_id || 'null';
                    bodyHtml += `
                        <tr class="hover:bg-purple-50 transition">
                            <td class="text-center py-3 px-4 text-gray-600">${index + 1}</td>
                            <td class="py-3 px-4"><span class="text-gray-800 font-semibold">${item.pos_item_name}</span></td>
                            <td class="text-center py-3 px-4 text-gray-600">${parseFloat(item.recipe_qty_per_item).toFixed(3)} ${item.unit}</td>
                            <td class="text-center py-3 px-4"><span class="text-purple-600 font-bold">${item.total_sold}</span></td>
                            <td class="text-center py-3 px-4"><span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-semibold">${parseFloat(item.total_rm_used).toFixed(3)} ${item.unit}</span></td>
                            <td class="text-center py-3 px-4">
                                <button class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white px-3 py-1.5 rounded-lg transition duration-200 flex items-center justify-center mx-auto text-sm" 
                                        onclick="showOrderDetails(${item.item_id}, ${modifierId}, '${item.pos_item_name.replace(/'/g, "\\'")}')"> 
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View Orders
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }

            $('#salesTableBody').html(bodyHtml);
        }

        function showOrderDetails(itemId, modifierId, itemName) {
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

            if (modifierId && modifierId !== null) {
                requestData.item_modifier_id = modifierId;
            }

            $.ajax({
                url: '{{ route("reports.rm-sales.details") }}',
                type: 'POST',
                data: requestData,
                beforeSend: function () {
                    $('#itemDetailsModal').removeClass('hidden');
                    $('#detailsTableBody').html(`
                        <tr>
                            <td colspan="5" class="text-center py-8">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600 mb-3"></div>
                                    <p class="text-gray-600">Loading order details...</p>
                                </div>
                            </td>
                        </tr>
                    `);
                },
                success: function (response) {
                    if (response.success) {
                        renderDetailsTable(response.transactions);
                    }
                },
                error: function (xhr) {
                    alert('Error loading order details');
                    console.error(xhr);
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
                        <td colspan="5" class="text-center py-8">
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
                            <td class="py-3 px-4 text-gray-800 font-medium">${row.customer}</td>
                            <td class="py-3 px-4 text-gray-600">${row.completed_at}</td>
                            <td class="text-center py-3 px-4 text-purple-600 font-bold">${row.quantity}</td>
                        </tr>
                    `;
                });
            }

            $('#detailsTableBody').html(html);
        }
    </script>
@endpush
