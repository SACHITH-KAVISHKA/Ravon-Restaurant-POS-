@extends('layouts.app')

@section('title', 'Stock Adjustment History')

@push('styles')
    <style>
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        /* Searchable Dropdown Styles */
        .searchable-dropdown {
            position: relative;
            width: 100%;
        }

        .searchable-dropdown .search-input {
            width: 100%;
            padding: 10px 40px 10px 14px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            background-color: white;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .searchable-dropdown .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .searchable-dropdown .search-input::placeholder {
            color: #9ca3af;
        }

        .searchable-dropdown .dropdown-arrow {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #6b7280;
            transition: transform 0.2s ease;
        }

        .searchable-dropdown.open .dropdown-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .searchable-dropdown .dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 4px;
            max-height: 280px;
            overflow-y: auto;
            z-index: 9999;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            display: none;
        }

        .searchable-dropdown.open .dropdown-list {
            display: block;
            animation: slideDown 0.15s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .searchable-dropdown .dropdown-item {
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.15s ease;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }

        .searchable-dropdown .dropdown-item:last-child {
            border-bottom: none;
        }

        .searchable-dropdown .dropdown-item:hover,
        .searchable-dropdown .dropdown-item.highlighted {
            background: linear-gradient(135deg, #f3e8ff 0%, #e0e7ff 100%);
        }

        .searchable-dropdown .dropdown-item.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .searchable-dropdown .dropdown-item .item-code {
            font-weight: 600;
            color: #667eea;
        }

        .searchable-dropdown .dropdown-item.selected .item-code {
            color: #e0e7ff;
        }

        .searchable-dropdown .no-results {
            padding: 16px;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
        }

        .searchable-dropdown .dropdown-list::-webkit-scrollbar {
            width: 6px;
        }

        .searchable-dropdown .dropdown-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .searchable-dropdown .dropdown-list::-webkit-scrollbar-thumb {
            background: #c5c5c5;
            border-radius: 3px;
        }

        .searchable-dropdown .clear-btn {
            position: absolute;
            right: 32px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            display: none;
            transition: all 0.15s ease;
        }

        .searchable-dropdown .clear-btn:hover {
            color: #ef4444;
            background: #fee2e2;
        }

        .searchable-dropdown.has-value .clear-btn {
            display: block;
        }

        /* History table styles */
        .history-table th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .history-table tbody tr {
            transition: background-color 0.15s ease;
        }

        .history-table tbody tr:hover {
            background-color: #f9fafb;
        }

        /* Badges */
        .badge-in {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .badge-out {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .badge-rm {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-cashier {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
        }

        .qty-positive {
            color: #059669;
            font-weight: 600;
        }

        .qty-negative {
            color: #dc2626;
            font-weight: 600;
        }

        /* Loading overlay */
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 20;
            border-radius: 12px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Detail Modal */
        .detail-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeInModal 0.2s ease;
        }

        @keyframes fadeInModal {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes scaleInModal {
            from {
                transform: scale(0.92);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .detail-modal-box {
            background: #fff;
            border-radius: 16px;
            max-width: 700px;
            width: 95%;
            box-shadow: 0 25px 60px rgba(99, 102, 241, 0.25);
            animation: scaleInModal 0.25s ease;
            overflow: hidden;
        }

        .detail-modal-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .detail-modal-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .detail-modal-header p {
            font-size: 0.82rem;
            opacity: 0.85;
            margin-top: 2px;
        }

        .detail-modal-body {
            padding: 0;
            max-height: 50vh;
            overflow-y: auto;
        }

        .detail-modal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-modal-table th {
            padding: 10px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .detail-modal-table td {
            padding: 12px 16px;
            font-size: 13px;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-modal-table tr:last-child td {
            border-bottom: none;
        }

        .detail-modal-table tr:hover td {
            background: #faf5ff;
        }

        .detail-modal-footer {
            padding: 16px 24px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid #667eea;
            color: #667eea;
            background: #fff;
            transition: all 0.2s ease;
        }

        .view-btn:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
    </style>
@endpush

@section('content')
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <x-sidebar />

        <!-- Main Content -->
        <div class="flex-1 p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1
                        class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                        Stock Adjustment History</h1>
                    <p class="text-gray-500 text-sm">View past stock adjustments and variance details</p>
                </div>
                <a href="{{ route('stock-adjustment.index') }}"
                    class="px-4 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white font-medium rounded-lg transition hover:shadow-lg hover:shadow-purple-500/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Adjustment
                </a>
            </div>

            <!-- Filters Card -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-visible mb-6">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800">Filters</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <!-- Item Filter (Searchable Dropdown) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Item</label>
                            <div class="searchable-dropdown" id="filterItemDropdown">
                                <input type="hidden" id="filter_item_id" class="item-value">
                                <input type="text" class="search-input" placeholder="🔍 All items..." autocomplete="off">
                                <button type="button" class="clear-btn" title="Clear selection">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                                <span class="dropdown-arrow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </span>
                                <div class="dropdown-list"></div>
                            </div>
                        </div>

                        <!-- From Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">From Date</label>
                            <input type="date" id="filter_from_date"
                                class="form-input w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-medium">
                        </div>

                        <!-- To Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">To Date</label>
                            <input type="date" id="filter_to_date"
                                class="form-input w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-medium">
                        </div>

                        <!-- Generate Button -->
                        <div>
                            <button type="button" id="generateBtn"
                                class="w-full px-6 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white font-semibold rounded-lg transition hover:shadow-lg hover:shadow-purple-500/30 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Generate
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden relative"
                id="resultsContainer">
                <div
                    class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-bold text-gray-800">Adjustment Records</h2>
                        <span id="recordCount"
                            class="text-xs font-medium text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full"></span>
                    </div>
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="tableSearchInput" placeholder="Search records..."
                            class="form-input pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm w-64 focus:border-purple-400 focus:ring-2 focus:ring-purple-200">
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[65vh] overflow-y-auto">
                    <table class="w-full history-table" id="historyTable">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
                                    Date & Time</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
                                    Type</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
                                    Reference</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
                                    Cashier</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
                                    Items</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
                                    Variance Amount</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="7" class="py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="spinner mb-4"></div>
                                        <p class="text-gray-400 text-sm font-medium">Loading today's adjustments...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="detail-modal-overlay" style="display:none;">
        <div class="detail-modal-box">
            <div class="detail-modal-header">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 id="detailModalTitle">Adjustment Details</h3>
                        <p id="detailModalSub"></p>
                    </div>
                    <button type="button" id="detailModalClose" class="text-white/70 hover:text-white transition p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="detail-modal-body">
                <table class="detail-modal-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>System Qty</th>
                            <th>Actual Qty</th>
                            <th>Variance Qty</th>
                            <th>Price</th>
                            <th>Variance Amt</th>
                        </tr>
                    </thead>
                    <tbody id="detailModalBody"></tbody>
                </table>
            </div>
            <div class="detail-modal-footer">
                <div>
                    <span class="text-xs text-gray-500" id="detailModalLocation"></span>
                    <span class="text-xs text-gray-400 ml-2" id="detailModalPerformer"></span>
                </div>
                <div class="text-sm font-bold" id="detailModalTotal"></div>
            </div>
        </div>
    </div>

    <!-- Items Data for JavaScript -->
    <script type="application/json" id="items-data">
                            {!! json_encode($items) !!}
                        </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemsData = JSON.parse(document.getElementById('items-data').textContent);
            const generateBtn = document.getElementById('generateBtn');
            const historyTableBody = document.getElementById('historyTableBody');
            const resultsContainer = document.getElementById('resultsContainer');

            // Set default dates to today
            const today = new Date();
            const todayStr = today.toISOString().slice(0, 10);
            document.getElementById('filter_to_date').value = todayStr;
            document.getElementById('filter_from_date').value = todayStr;

            // ── Filter Item Dropdown ──
            const filterDropdown = document.getElementById('filterItemDropdown');
            const filterSearchInput = filterDropdown.querySelector('.search-input');
            const filterHiddenInput = filterDropdown.querySelector('.item-value');
            const filterClearBtn = filterDropdown.querySelector('.clear-btn');
            const filterList = filterDropdown.querySelector('.dropdown-list');
            let filterHighlightedIndex = -1;
            let filterCurrentItems = [];

            function getFilteredItemsList(query) {
                const q = query.toLowerCase().trim();
                return itemsData.filter(item => {
                    if (!q) return true;
                    return item.code.toLowerCase().includes(q) ||
                        item.name.toLowerCase().includes(q);
                });
            }

            function renderFilterDropdownItems(items, highlightIndex = -1) {
                const currentValue = filterHiddenInput.value;
                if (items.length === 0) {
                    filterList.innerHTML = '<div class="no-results">No items found</div>';
                    return;
                }
                filterList.innerHTML = items.map((item, index) => `
                                        <div class="dropdown-item${item.id == currentValue ? ' selected' : ''}${index === highlightIndex ? ' highlighted' : ''}"
                                             data-id="${item.id}"
                                             data-code="${item.code}"
                                             data-name="${item.name}">
                                            <span class="item-code">${item.code}</span> - <span class="item-name">${item.name}</span>
                                        </div>
                                    `).join('');
            }

            filterSearchInput.addEventListener('click', openFilterDropdown);
            filterSearchInput.addEventListener('focus', openFilterDropdown);

            filterSearchInput.addEventListener('input', function () {
                filterCurrentItems = getFilteredItemsList(this.value);
                filterHighlightedIndex = -1;
                renderFilterDropdownItems(filterCurrentItems, filterHighlightedIndex);
                if (!filterDropdown.classList.contains('open')) {
                    filterDropdown.classList.add('open');
                }
            });

            filterSearchInput.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!filterDropdown.classList.contains('open')) { openFilterDropdown(); }
                    else {
                        filterHighlightedIndex = Math.min(filterHighlightedIndex + 1, filterCurrentItems.length - 1);
                        renderFilterDropdownItems(filterCurrentItems, filterHighlightedIndex);
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    filterHighlightedIndex = Math.max(filterHighlightedIndex - 1, 0);
                    renderFilterDropdownItems(filterCurrentItems, filterHighlightedIndex);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (filterHighlightedIndex >= 0 && filterCurrentItems[filterHighlightedIndex]) {
                        selectFilterItem(filterCurrentItems[filterHighlightedIndex]);
                    }
                } else if (e.key === 'Escape') {
                    closeFilterDropdown();
                }
            });

            filterClearBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                filterHiddenInput.value = '';
                filterSearchInput.value = '';
                filterDropdown.classList.remove('has-value');
                filterSearchInput.focus();
            });

            filterList.addEventListener('click', function (e) {
                const item = e.target.closest('.dropdown-item');
                if (item) {
                    selectFilterItem({
                        id: item.dataset.id,
                        code: item.dataset.code,
                        name: item.dataset.name,
                    });
                }
            });

            document.addEventListener('click', function (e) {
                if (!filterDropdown.contains(e.target)) {
                    closeFilterDropdown();
                }
            });

            function openFilterDropdown() {
                filterCurrentItems = getFilteredItemsList(filterSearchInput.value);
                renderFilterDropdownItems(filterCurrentItems, filterHighlightedIndex);
                filterDropdown.classList.add('open');
            }

            function closeFilterDropdown() {
                filterDropdown.classList.remove('open');
                filterHighlightedIndex = -1;
                if (filterHiddenInput.value) {
                    const item = itemsData.find(i => i.id == filterHiddenInput.value);
                    if (item) filterSearchInput.value = `${item.code} - ${item.name}`;
                }
            }

            function selectFilterItem(itemData) {
                filterHiddenInput.value = itemData.id;
                filterSearchInput.value = `${itemData.code} - ${itemData.name}`;
                filterDropdown.classList.add('has-value');
                closeFilterDropdown();
            }

            // ── Generate / Fetch History ──
            generateBtn.addEventListener('click', fetchHistory);

            // Auto-load today's adjustments on page load
            fetchHistory();

            async function fetchHistory() {
                const params = new URLSearchParams();

                if (filterHiddenInput.value) {
                    params.append('item_id', filterHiddenInput.value);
                }
                if (document.getElementById('filter_from_date').value) {
                    params.append('from_date', document.getElementById('filter_from_date').value);
                }
                if (document.getElementById('filter_to_date').value) {
                    params.append('to_date', document.getElementById('filter_to_date').value);
                }

                // Show loading
                let loadingOverlay = resultsContainer.querySelector('.loading-overlay');
                if (!loadingOverlay) {
                    loadingOverlay = document.createElement('div');
                    loadingOverlay.className = 'loading-overlay';
                    loadingOverlay.innerHTML = '<div class="spinner"></div>';
                    resultsContainer.appendChild(loadingOverlay);
                }
                loadingOverlay.style.display = 'flex';

                try {
                    const response = await fetch(`{{ route('stock-adjustment.get-history') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Group by adjustment_id
                        const grouped = groupByAdjustmentId(result.data);
                        allGroupedData = grouped;
                        tableSearchInput.value = '';
                        renderHistory(grouped);
                        document.getElementById('recordCount').textContent = `${grouped.length} adjustment(s)`;
                    } else {
                        showToast('Failed to load history', 'error');
                    }
                } catch (err) {
                    showToast('Error loading history: ' + err.message, 'error');
                } finally {
                    loadingOverlay.style.display = 'none';
                }
            }

            function groupByAdjustmentId(data) {
                const map = {};
                data.forEach(item => {
                    if (!map[item.adjustment_id]) {
                        map[item.adjustment_id] = {
                            adjustment_id: item.adjustment_id,
                            date_display: item.date_display,
                            date_time: item.date_time,
                            cashier_name: item.cashier_name,
                            performed_by: item.performed_by,
                            stock_location: item.stock_location,
                            items: [],
                            total_variance: 0,
                        };
                    }
                    map[item.adjustment_id].items.push(item);
                    map[item.adjustment_id].total_variance += parseFloat(String(item.variance_amount).replace(/,/g, ''));
                });
                return Object.values(map).sort((a, b) => b.date_time.localeCompare(a.date_time));
            }

            function renderHistory(groups) {
                if (groups.length === 0) {
                    historyTableBody.innerHTML = `
                            <tr>
                                <td colspan="7" class="py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-gray-400 text-sm font-medium">No adjustment records found</p>
                                    </div>
                                </td>
                            </tr>`;
                    return;
                }

                historyTableBody.innerHTML = groups.map(group => {
                    const totalVar = group.total_variance;
                    const varClass = totalVar >= 0 ? 'qty-positive' : 'qty-negative';
                    const varDisplay = (totalVar >= 0 ? '+' : '') + totalVar.toFixed(2);
                    const itemCount = group.items.length;

                    return `
                            <tr class="border-b border-gray-100 hover:bg-purple-50/30 transition">
                                <td class="py-3 px-4 text-sm text-gray-700">${group.date_display}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        Stock Adjustment
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-sm font-mono font-semibold text-purple-600">${group.adjustment_id}</span>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600">${group.cashier_name}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">${itemCount}</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="${varClass} text-sm font-semibold">Rs. ${varDisplay}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" class="view-btn" onclick="showDetail('${group.adjustment_id}')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View
                                    </button>
                                </td>
                            </tr>`;
                }).join('');
            }

            // ── Detail Modal ──
            const detailModal = document.getElementById('detailModal');
            const detailModalClose = document.getElementById('detailModalClose');

            detailModalClose.addEventListener('click', () => detailModal.style.display = 'none');
            detailModal.addEventListener('click', (e) => {
                if (e.target === detailModal) detailModal.style.display = 'none';
            });

            window.showDetail = function (adjustmentId) {
                const group = allGroupedData.find(g => g.adjustment_id === adjustmentId);
                if (!group) return;

                document.getElementById('detailModalTitle').textContent = adjustmentId;
                document.getElementById('detailModalSub').textContent = `${group.date_display} \u2022 Cashier: ${group.cashier_name}`;

                const locationBadge = group.stock_location === 'RM'
                    ? '<span class="badge-rm">RM Stock</span>'
                    : '<span class="badge-cashier">Cashier Stock</span>';
                document.getElementById('detailModalLocation').innerHTML = locationBadge;
                document.getElementById('detailModalPerformer').textContent = `Performed by: ${group.performed_by}`;

                const tbody = document.getElementById('detailModalBody');
                tbody.innerHTML = group.items.map((item, i) => {
                    const vqClass = parseFloat(String(item.variance_qty).replace(/,/g, '')) >= 0 ? 'qty-positive' : 'qty-negative';
                    const vaClass = parseFloat(String(item.variance_amount).replace(/,/g, '')) >= 0 ? 'qty-positive' : 'qty-negative';
                    return `
                            <tr>
                                <td class="text-gray-400 font-medium">${i + 1}</td>
                                <td>
                                    <div class="font-medium text-gray-800">${item.item_name}</div>
                                    <div class="text-xs text-gray-400">${item.item_code}</div>
                                </td>
                                <td class="text-gray-600">${item.system_qty}</td>
                                <td class="text-gray-800 font-medium">${item.actual_qty}</td>
                                <td class="${vqClass} font-semibold">${item.quantity_display}</td>
                                <td class="text-gray-600">Rs. ${item.price}</td>
                                <td class="${vaClass} font-semibold">Rs. ${item.variance_amount}</td>
                            </tr>`;
                }).join('');

                const totalVar = group.total_variance;
                const totalClass = totalVar >= 0 ? 'qty-positive' : 'qty-negative';
                document.getElementById('detailModalTotal').innerHTML = `Total: <span class="${totalClass}">Rs. ${(totalVar >= 0 ? '+' : '') + totalVar.toFixed(2)}</span>`;

                detailModal.style.display = 'flex';
            };

            // ── Table Search ──
            let allGroupedData = [];
            const tableSearchInput = document.getElementById('tableSearchInput');

            tableSearchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                if (!query) {
                    renderHistory(allGroupedData);
                    document.getElementById('recordCount').textContent = `${allGroupedData.length} adjustment(s)`;
                    return;
                }
                const filtered = allGroupedData.filter(group => {
                    const itemNames = group.items.map(i => i.item_name).join(' ').toLowerCase();
                    return group.adjustment_id.toLowerCase().includes(query) ||
                        group.cashier_name.toLowerCase().includes(query) ||
                        group.date_display.toLowerCase().includes(query) ||
                        itemNames.includes(query);
                });
                renderHistory(filtered);
                document.getElementById('recordCount').textContent = `${filtered.length} of ${allGroupedData.length} adjustment(s)`;
            });
        });
    </script>
@endsection