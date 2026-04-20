@extends('layouts.app')

@section('title', 'RM Sales Report - Ravon Restaurant POS')

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

        .searchable-dropdown {
            position: relative;
            width: 100%;
        }

        .searchable-dropdown .search-input {
            width: 100%;
            padding: 0.5rem 2.5rem 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            background: #fff;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .searchable-dropdown .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.18);
        }

        .searchable-dropdown .dropdown-arrow {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #6b7280;
            transition: transform 0.2s ease;
        }

        .searchable-dropdown.open .dropdown-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .searchable-dropdown .clear-btn {
            position: absolute;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            display: none;
            background: none;
            border: none;
            color: #9ca3af;
            padding: 0.25rem;
            border-radius: 0.25rem;
        }

        .searchable-dropdown.has-value .clear-btn {
            display: block;
        }

        .searchable-dropdown .clear-btn:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        .searchable-dropdown .dropdown-list {
            position: absolute;
            top: calc(100% + 0.25rem);
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            max-height: 18rem;
            overflow-y: auto;
            z-index: 9999;
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.2);
            display: none;
        }

        .searchable-dropdown.open .dropdown-list {
            display: block;
        }

        .searchable-dropdown .dropdown-item {
            padding: 0.625rem 0.875rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }

        .searchable-dropdown .dropdown-item:last-child {
            border-bottom: none;
        }

        .searchable-dropdown .dropdown-item:hover,
        .searchable-dropdown .dropdown-item.highlighted {
            background: #f3f4ff;
        }

        .searchable-dropdown .dropdown-item.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .searchable-dropdown .dropdown-item .item-code {
            font-weight: 700;
            color: #667eea;
        }

        .searchable-dropdown .dropdown-item.selected .item-code {
            color: #e9e7ff;
        }

        .searchable-dropdown .no-results {
            padding: 0.875rem;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
        }

        .searchable-dropdown .dropdown-list::-webkit-scrollbar {
            width: 6px;
        }

        .searchable-dropdown .dropdown-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
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
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">Raw Material Sales Report</h1>
                    <p class="text-gray-600">Analyze raw material usage based on POS item sales over a selected period.</p>
                </div>

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form id="filterForm" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                            <!-- Raw Material -->
                            <div class="col-span-1 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Raw Material</label>
                                <div class="searchable-dropdown" id="rawMaterialDropdown">
                                    <input type="hidden" id="raw_material_id" name="raw_material_id" required>
                                    <input type="text" class="search-input" placeholder="Search raw material..." autocomplete="off">
                                    <button type="button" class="clear-btn" title="Clear selection">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <span class="dropdown-arrow">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </span>
                                    <div class="dropdown-list"></div>
                                </div>
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="{{ date('Y-m-d') }}"
                                    max="{{ date('Y-m-d') }}" required
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="{{ date('Y-m-d') }}"
                                    max="{{ date('Y-m-d') }}" required
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Actions -->
                            <div>
                                <button type="submit"
                                class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-8 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Summary Stats -->
                <div id="summaryStats" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6" style="display: none;">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 flex flex-col justify-center items-center">
                        <h3 class="text-gray-500 text-sm font-semibold mb-1">Raw Material</h3>
                        <p class="text-2xl font-bold text-gray-800" id="summaryRmName">-</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 flex flex-col justify-center items-center">
                        <h3 class="text-gray-500 text-sm font-semibold mb-1">Total Used In Sales Period</h3>
                        <p class="text-3xl font-bold text-purple-600"><span id="summaryTotalUsed">-</span> <span id="summaryUnit" class="text-lg text-gray-500"></span></p>
                    </div>
                </div>

                <!-- Data Table Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="table-responsive">
                        <table class="w-full" id="salesTable">
                            <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <tr>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">#</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">POS Item Name</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Recipe Qty / Unit</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Item Qty Sold</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Total RM Used</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Actions</th>
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

    <!-- Order Details Modal -->
    <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="itemDetailsModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="modal-backdrop fixed inset-0"></div>
            <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 max-w-6xl w-full">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-purple-100">
                    <h3 class="text-xl font-bold text-purple-700">Order Transactions For Item</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">POS Item</p>
                        <h6 class="text-lg font-semibold text-gray-800"><span id="modalItemName" class="text-purple-700"></span></h6>
                    </div>

                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <tr>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Order #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Customer</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Date & Time</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Qty Sold</th>
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
    @php
        $rawMaterialsData = $rawMaterials->map(function ($rm) {
            return [
                'id' => $rm->id,
                'code' => $rm->item_code,
                'name' => $rm->item_name,
            ];
        })->values();
    @endphp
    <script>
        let currentItemId = null;
        let currentModifierId = null;
        const rawMaterialsData = @json($rawMaterialsData);

        $(document).ready(function () {
            setupRawMaterialDropdown();

            // Filter form submission
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                loadReportData();
            });

            $('.modal-backdrop').on('click', function (e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });

        function setupRawMaterialDropdown() {
            const dropdown = document.getElementById('rawMaterialDropdown');
            const searchInput = dropdown.querySelector('.search-input');
            const hiddenInput = dropdown.querySelector('#raw_material_id');
            const clearBtn = dropdown.querySelector('.clear-btn');
            const list = dropdown.querySelector('.dropdown-list');
            let highlightedIndex = -1;
            let currentItems = [];

            function getFilteredItems(query) {
                const q = query.toLowerCase().trim();
                return rawMaterialsData.filter(item => {
                    if (!q) return true;
                    return item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q);
                });
            }

            function renderItems(items) {
                if (items.length === 0) {
                    list.innerHTML = '<div class="no-results">No raw materials found</div>';
                    return;
                }

                list.innerHTML = items.map((item, index) => `
                    <div class="dropdown-item${String(item.id) === String(hiddenInput.value) ? ' selected' : ''}${index === highlightedIndex ? ' highlighted' : ''}"
                         data-id="${item.id}"
                         data-code="${item.code}"
                         data-name="${item.name}">
                        <span class="item-code">${item.code}</span> - <span class="item-name">${item.name}</span>
                    </div>
                `).join('');
            }

            function openDropdown() {
                currentItems = getFilteredItems(searchInput.value);
                renderItems(currentItems);
                dropdown.classList.add('open');
            }

            function closeDropdown() {
                dropdown.classList.remove('open');
                highlightedIndex = -1;
                if (hiddenInput.value) {
                    const selected = rawMaterialsData.find(item => String(item.id) === String(hiddenInput.value));
                    if (selected) {
                        searchInput.value = `${selected.code} - ${selected.name}`;
                    }
                }
            }

            function selectItem(item) {
                hiddenInput.value = item.id;
                searchInput.value = `${item.code} - ${item.name}`;
                dropdown.classList.add('has-value');
                closeDropdown();
            }

            searchInput.addEventListener('click', openDropdown);
            searchInput.addEventListener('focus', openDropdown);
            searchInput.addEventListener('input', function () {
                currentItems = getFilteredItems(this.value);
                highlightedIndex = -1;
                renderItems(currentItems);
                dropdown.classList.add('open');
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!dropdown.classList.contains('open')) {
                        openDropdown();
                        return;
                    }
                    highlightedIndex = Math.min(highlightedIndex + 1, currentItems.length - 1);
                    renderItems(currentItems);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    highlightedIndex = Math.max(highlightedIndex - 1, 0);
                    renderItems(currentItems);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlightedIndex >= 0 && currentItems[highlightedIndex]) {
                        selectItem(currentItems[highlightedIndex]);
                    }
                } else if (e.key === 'Escape') {
                    closeDropdown();
                }
            });

            clearBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                hiddenInput.value = '';
                searchInput.value = '';
                dropdown.classList.remove('has-value');
                searchInput.focus();
            });

            list.addEventListener('click', function (e) {
                const item = e.target.closest('.dropdown-item');
                if (!item) return;
                selectItem({
                    id: item.dataset.id,
                    code: item.dataset.code,
                    name: item.dataset.name,
                });
            });

            document.addEventListener('click', function (e) {
                if (!dropdown.contains(e.target)) {
                    closeDropdown();
                }
            });
        }

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
                    $('#summaryStats').hide();
                    $('#salesTableBody').html(`
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                                    <p class="text-gray-600">Loading data...</p>
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
            $('#summaryStats').show();
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
                        <tr class="hover:bg-purple-50 transition-colors">
                            <td class="px-6 py-4 text-center text-gray-600">${index + 1}</td>
                            <td class="px-6 py-4"><span class="text-gray-800 font-semibold">${item.pos_item_name}</span></td>
                            <td class="px-6 py-4 text-center text-gray-600">${parseFloat(item.recipe_qty_per_item).toFixed(3)} ${item.unit}</td>
                            <td class="px-6 py-4 text-center"><span class="text-purple-600 font-bold">${item.total_sold}</span></td>
                            <td class="px-6 py-4 text-center"><span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-semibold">${parseFloat(item.total_rm_used).toFixed(3)} ${item.unit}</span></td>
                            <td class="px-6 py-4 text-center">
                                <button class="btn-action bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white flex items-center justify-center mx-auto text-sm" 
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
                        <tr class="hover:bg-purple-50 transition-colors bg-white">
                            <td class="px-4 py-3 text-center text-gray-600">${index + 1}</td>
                            <td class="px-4 py-3"><span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">${row.order_number}</span></td>
                            <td class="px-4 py-3 text-gray-800 font-medium">${row.customer}</td>
                            <td class="px-4 py-3 text-gray-600">${row.completed_at}</td>
                            <td class="px-4 py-3 text-center text-purple-600 font-bold">${row.quantity}</td>
                        </tr>
                    `;
                });
            }

            $('#detailsTableBody').html(html);
        }
    </script>
@endpush
