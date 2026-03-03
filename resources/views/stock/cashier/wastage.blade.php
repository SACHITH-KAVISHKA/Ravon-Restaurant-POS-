@extends('layouts.app')

@section('title', 'Wastage - Cashier')

@push('styles')
    <style>
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .item-row {
            transition: all 0.2s ease;
            position: relative;
        }

        .item-row:hover {
            background-color: #fef2f2;
        }

        .delete-btn:hover {
            background-color: #fee2e2;
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

        .item-row td:first-child {
            overflow: visible !important;
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
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }

        .searchable-dropdown .dropdown-item.selected {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .searchable-dropdown .dropdown-item .item-code {
            font-weight: 600;
            color: #ef4444;
        }

        .searchable-dropdown .dropdown-item.selected .item-code {
            color: #fecaca;
        }

        .searchable-dropdown .dropdown-item .item-name {
            color: #374151;
        }

        .searchable-dropdown .dropdown-item.selected .item-name {
            color: white;
        }

        .searchable-dropdown .dropdown-item .item-type-badge {
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 4px;
            margin-left: 6px;
        }

        .searchable-dropdown .no-results {
            padding: 16px;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
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

        /* Total wastage bar */
        .total-wastage-bar {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 12px;
            padding: 16px 24px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Readonly input styling */
        input[readonly] {
            background-color: #f1f5f9 !important;
            cursor: default;
        }

        .price-input {
            background-color: #eef2ff !important;
            border-color: #c7d2fe !important;
            color: #3730a3 !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
        }

        .current-qty-input {
            background-color: #f0fdf4 !important;
            border-color: #bbf7d0 !important;
            color: #166534 !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
        }

        .waste-qty-input {
            background-color: #fff !important;
            color: #dc2626 !important;
            font-weight: 800 !important;
            font-size: 0.92rem !important;
        }

        .waste-amount-input {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #dc2626 !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
        }

        /* Category filter select */
        .category-select {
            padding: 6px 32px 6px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            color: #374151;
        }

        .category-select:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        /* Reason select */
        .reason-select {
            padding: 6px 12px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .reason-select:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            z-index: 99999;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            max-width: 400px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-success {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }

        .toast-error {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        /* Confirm modal */
        .confirm-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .confirm-modal-box {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .confirm-modal-icon {
            color: #ef4444;
            margin-bottom: 16px;
        }

        /* History panel */
        .history-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .history-panel.open {
            max-height: 1000px;
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
                        class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-orange-500">
                        Stock Wastage</h1>
                    <p class="text-gray-500 text-sm">Record wasted or damaged stock items from your inventory</p>
                </div>
                <button type="button" id="toggleHistoryBtn"
                    class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 font-medium rounded-lg transition flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    My Wastage History
                </button>
            </div>

            <!-- History Panel (collapsible) -->
            <div class="history-panel mb-6" id="historyPanel">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-red-500 to-orange-500 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-white">My Wastage History</h2>
                        <div class="flex items-center gap-2">
                            <input type="date" id="histFromDate"
                                class="px-3 py-1.5 rounded-lg text-sm border-0 font-medium">
                            <span class="text-white text-sm">to</span>
                            <input type="date" id="histToDate" class="px-3 py-1.5 rounded-lg text-sm border-0 font-medium">
                            <button id="loadHistoryBtn"
                                class="px-3 py-1.5 bg-white/20 text-white rounded-lg text-sm font-medium hover:bg-white/30 transition">
                                Load
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600">#</th>
                                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600">Date</th>
                                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600">ID</th>
                                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600">Item</th>
                                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-600">Qty Wasted
                                        </th>
                                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-600">Amount</th>
                                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600">Reason</th>
                                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600">Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center py-8 text-gray-400">Click "Load" to view history
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wastage Items Table -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-6">
                <div
                    class="px-6 py-4 bg-gradient-to-r from-red-50 to-orange-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Wastage Items</h2>
                    <div class="flex items-center gap-2">
                        <label for="categoryFilter" class="text-sm font-semibold text-gray-600">Category:</label>
                        <select id="categoryFilter" class="category-select">
                            <option value="all" selected>All</option>
                            <option value="finished_good">FG</option>
                            <option value="raw_material">RM</option>
                        </select>
                    </div>
                </div>

                <div class="p-4" style="overflow: visible;">
                    <div class="border border-gray-200 rounded-lg" style="overflow: visible;">
                        <table class="w-full" id="wastageTable">
                            <thead>
                                <tr class="bg-gradient-to-r from-red-50 to-orange-50 border-b border-gray-200">
                                    <th class="text-left py-3 px-3 text-xs font-semibold text-gray-700 uppercase"
                                        style="min-width: 180px; max-width: 320px;">Item</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-green-700 uppercase w-20">
                                        Available</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-indigo-700 uppercase w-20">
                                        Price</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-red-700 uppercase w-24">
                                        Waste Qty</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-red-700 uppercase w-24">
                                        Amount</th>
                                    <th class="text-left py-3 px-3 text-xs font-semibold text-gray-700 uppercase w-36">
                                        Reason</th>
                                    <th class="text-left py-3 px-3 text-xs font-semibold text-gray-700 uppercase w-36">Notes
                                    </th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-gray-700 uppercase w-14">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total Wastage -->
                <div class="px-6 pb-6">
                    <div class="total-wastage-bar" id="totalWastageBar">
                        <span>Total Wastage Amount</span>
                        <span id="totalWastageAmount">Rs. 0.00</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center px-6 pb-6">
                    <button type="button" id="addRowBtn"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Row
                    </button>
                    <div class="flex gap-3">
                        <button type="button" id="clearFormBtn"
                            class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                            Clear Form
                        </button>
                        <button type="button" id="submitBtn"
                            class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-orange-500 hover:shadow-lg hover:shadow-red-500/50 text-white font-semibold rounded-lg transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Record Wastage
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal-overlay" style="display:none;">
        <div class="confirm-modal-box">
            <div class="confirm-modal-icon">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Confirm Wastage</h3>
            <p id="confirmModalMessage" class="text-gray-600 text-sm mb-6"></p>
            <div class="flex justify-center gap-3">
                <button id="confirmModalCancel"
                    class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button id="confirmModalOk"
                    class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-orange-500 text-white rounded-lg font-semibold hover:shadow-lg transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Available items from server
            const allItems = @json($items);
            const reasons = @json($reasons);
            let rowCounter = 0;
            let activeDropdown = null;
            const selectedFilter = document.getElementById('categoryFilter');

            // Add initial row
            addNewRow();

            // Add Row button
            document.getElementById('addRowBtn').addEventListener('click', addNewRow);

            // Category filter
            selectedFilter.addEventListener('change', () => {
                // Close any open dropdowns and re-filter
                closeAllDropdowns();
            });

            function getFilteredItems() {
                const filter = selectedFilter.value;
                if (filter === 'all') return allItems;
                return allItems.filter(item => item.type === filter);
            }

            function addNewRow() {
                rowCounter++;
                const tbody = document.getElementById('itemsTableBody');
                const row = document.createElement('tr');
                row.className = 'item-row border-b border-gray-100';
                row.dataset.rowId = rowCounter;

                row.innerHTML = `
                    <td class="py-2 px-3" style="overflow: visible;">
                        <div class="searchable-dropdown" data-row="${rowCounter}">
                            <input type="text" class="search-input" placeholder="Search item..." autocomplete="off">
                            <svg class="dropdown-arrow w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <button class="clear-btn" title="Clear">✕</button>
                            <div class="dropdown-list"></div>
                            <input type="hidden" class="item-id-input" value="">
                        </div>
                    </td>
                    <td class="py-2 px-3">
                        <input type="text" class="current-qty-input form-input w-full px-3 py-2 border-2 rounded-lg text-center text-sm" readonly value="0">
                    </td>
                    <td class="py-2 px-3">
                        <input type="text" class="price-input form-input w-full px-3 py-2 border-2 rounded-lg text-center text-sm" readonly value="0.00">
                    </td>
                    <td class="py-2 px-3">
                        <input type="number" class="waste-qty-input form-input w-full px-3 py-2 border-2 border-red-300 rounded-lg text-center text-sm" 
                            step="1" min="1" value="" placeholder="0"
                            oninput="calculateWastage(this)">
                    </td>
                    <td class="py-2 px-3">
                        <input type="text" class="waste-amount-input form-input w-full px-3 py-2 border-2 rounded-lg text-center text-sm" readonly value="0.00">
                    </td>
                    <td class="py-2 px-3">
                        <select class="reason-select">
                            <option value="">Select reason</option>
                            ${Object.entries(reasons).map(([key, label]) => `<option value="${key}">${label}</option>`).join('')}
                        </select>
                    </td>
                    <td class="py-2 px-3">
                        <input type="text" class="notes-input form-input w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs" placeholder="Notes...">
                    </td>
                    <td class="py-2 px-3 text-center">
                        <button type="button" class="delete-btn p-1.5 rounded-lg text-red-400 hover:text-red-600 transition" onclick="removeRow(this)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </td>
                `;

                tbody.appendChild(row);
                initSearchableDropdown(row.querySelector('.searchable-dropdown'));
            }

            function initSearchableDropdown(container) {
                const input = container.querySelector('.search-input');
                const clearBtn = container.querySelector('.clear-btn');
                const dropdownList = container.querySelector('.dropdown-list');
                const hiddenInput = container.querySelector('.item-id-input');
                let selectedItemId = null;

                input.addEventListener('focus', () => {
                    closeAllDropdowns();
                    renderDropdown('');
                    container.classList.add('open');
                    activeDropdown = container;
                });

                input.addEventListener('input', () => {
                    renderDropdown(input.value);
                    if (!container.classList.contains('open')) {
                        container.classList.add('open');
                    }
                });

                clearBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    input.value = '';
                    hiddenInput.value = '';
                    selectedItemId = null;
                    container.classList.remove('has-value');
                    closeAllDropdowns();
                    clearRowData(container.closest('tr'));
                });

                function renderDropdown(search) {
                    const items = getFilteredItems();
                    const usedIds = getUsedItemIds(container);
                    const filtered = items.filter(item => {
                        if (usedIds.includes(item.id.toString())) return false;
                        const term = search.toLowerCase();
                        return item.name.toLowerCase().includes(term) ||
                            (item.code && item.code.toLowerCase().includes(term));
                    });

                    if (filtered.length === 0) {
                        dropdownList.innerHTML = '<div class="no-results">No items found</div>';
                        return;
                    }

                    dropdownList.innerHTML = filtered.map(item => {
                        const isSelected = item.id.toString() === selectedItemId;
                        const isFG = item.type === 'finished_good';
                        const typeBadge = isFG
                            ? '<span class="item-type-badge" style="background:#dcfce7;color:#166534;">FG</span>'
                            : '<span class="item-type-badge" style="background:#fef3c7;color:#92400e;">RM</span>';
                        const displayQty = isFG ? Math.floor(item.current_qty) : parseFloat(item.current_qty).toFixed(3);
                        const isFinished = parseFloat(item.current_qty) <= 0;
                        const finishedBadge = isFinished ? '<span style="float:right;font-size:10px;background:#fee2e2;color:#dc2626;padding:1px 6px;border-radius:4px;margin-left:4px;">Finished</span>' : '';
                        return `
                            <div class="dropdown-item ${isSelected ? 'selected' : ''}" data-id="${item.id}">
                                <span class="item-code">${item.code || ''}</span>
                                <span class="item-name" style="margin-left:6px;">${item.name}</span>
                                ${typeBadge}
                                ${finishedBadge}
                                <span style="float:right;font-size:11px;color:${isFinished ? '#dc2626' : '#6b7280'};">Avail: ${displayQty} ${item.unit}</span>
                            </div>
                        `;
                    }).join('');

                    dropdownList.querySelectorAll('.dropdown-item').forEach(el => {
                        el.addEventListener('click', () => {
                            const id = el.dataset.id;
                            const item = items.find(i => i.id.toString() === id);
                            if (item) {
                                selectItem(item);
                            }
                        });
                    });
                }

                function selectItem(item) {
                    selectedItemId = item.id.toString();
                    input.value = `${item.code ? item.code + ' - ' : ''}${item.name}`;
                    hiddenInput.value = item.id;
                    container.classList.add('has-value');
                    container.classList.remove('open');

                    // Fill row data
                    const row = container.closest('tr');
                    const isFG = item.type === 'finished_good';
                    const displayQty = isFG ? Math.floor(parseFloat(item.current_qty)) : parseFloat(item.current_qty).toFixed(3);
                    row.querySelector('.current-qty-input').value = displayQty;
                    row.dataset.itemType = item.type;

                    // Adjust waste qty input for FG (whole numbers) vs RM (decimals)
                    const wasteQtyInput = row.querySelector('.waste-qty-input');
                    if (isFG) {
                        wasteQtyInput.step = '1';
                        wasteQtyInput.min = '1';
                        wasteQtyInput.placeholder = '0';
                    } else {
                        wasteQtyInput.step = '0.001';
                        wasteQtyInput.min = '0.001';
                        wasteQtyInput.placeholder = '0.000';
                    }

                    // Show finished indicator if stock is 0
                    const qtyInput = row.querySelector('.current-qty-input');
                    if (parseFloat(item.current_qty) <= 0) {
                        qtyInput.style.backgroundColor = '#fef2f2';
                        qtyInput.style.borderColor = '#fecaca';
                        qtyInput.style.color = '#dc2626';
                        qtyInput.value = isFG ? '0' : '0.000';
                    } else {
                        qtyInput.style.backgroundColor = '';
                        qtyInput.style.borderColor = '';
                        qtyInput.style.color = '';
                    }

                    row.querySelector('.price-input').value = parseFloat(item.price).toFixed(2);
                    wasteQtyInput.value = '';
                    row.querySelector('.waste-amount-input').value = '0.00';
                    wasteQtyInput.focus();
                }
            }

            function getUsedItemIds(exceptContainer) {
                const ids = [];
                document.querySelectorAll('.searchable-dropdown').forEach(c => {
                    if (c !== exceptContainer) {
                        const val = c.querySelector('.item-id-input').value;
                        if (val) ids.push(val);
                    }
                });
                return ids;
            }

            function closeAllDropdowns() {
                document.querySelectorAll('.searchable-dropdown').forEach(c => {
                    c.classList.remove('open');
                });
                activeDropdown = null;
            }

            function clearRowData(row) {
                row.querySelector('.current-qty-input').value = '0';
                row.querySelector('.price-input').value = '0.00';
                const wasteQtyInput = row.querySelector('.waste-qty-input');
                wasteQtyInput.value = '';
                wasteQtyInput.step = '1';
                wasteQtyInput.min = '1';
                wasteQtyInput.placeholder = '0';
                row.querySelector('.waste-amount-input').value = '0.00';
                row.querySelector('.reason-select').value = '';
                row.querySelector('.notes-input').value = '';
                delete row.dataset.itemType;
                updateTotalWastage();
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.searchable-dropdown')) {
                    closeAllDropdowns();
                }
            });

            // Calculate wastage amount for a row
            window.calculateWastage = function (input) {
                const row = input.closest('tr');
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const wasteQty = parseFloat(input.value) || 0;
                const currentQty = parseFloat(row.querySelector('.current-qty-input').value) || 0;

                // Validate
                if (wasteQty > currentQty) {
                    input.style.borderColor = '#ef4444';
                    input.style.backgroundColor = '#fef2f2';
                } else {
                    input.style.borderColor = '#fca5a5';
                    input.style.backgroundColor = '#fff';
                }

                const amount = wasteQty * price;
                row.querySelector('.waste-amount-input').value = amount.toFixed(2);
                updateTotalWastage();
            };

            function updateTotalWastage() {
                let total = 0;
                document.querySelectorAll('.waste-amount-input').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                document.getElementById('totalWastageAmount').textContent = 'Rs. ' + total.toFixed(2);
            }

            window.removeRow = function (btn) {
                const tbody = document.getElementById('itemsTableBody');
                if (tbody.children.length <= 1) {
                    showToast('At least one row is required', 'error');
                    return;
                }
                btn.closest('tr').remove();
                updateTotalWastage();
            };

            // Clear form
            document.getElementById('clearFormBtn').addEventListener('click', () => {
                document.getElementById('itemsTableBody').innerHTML = '';
                rowCounter = 0;
                addNewRow();
                updateTotalWastage();
            });

            // Submit wastage
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.addEventListener('click', async () => {
                const rows = document.querySelectorAll('#itemsTableBody tr');
                const items = [];

                for (const row of rows) {
                    const itemId = row.querySelector('.item-id-input').value;
                    const isFG = row.dataset.itemType === 'finished_good';
                    const wasteQty = isFG ? parseInt(row.querySelector('.waste-qty-input').value) : parseFloat(row.querySelector('.waste-qty-input').value);
                    const currentQty = parseFloat(row.querySelector('.current-qty-input').value);
                    const reason = row.querySelector('.reason-select').value;
                    const notes = row.querySelector('.notes-input').value;

                    if (!itemId) continue; // Skip empty rows

                    if (!wasteQty || wasteQty <= 0) {
                        showToast('Please enter waste quantity for all selected items', 'error');
                        return;
                    }

                    if (wasteQty > currentQty) {
                        showToast('Waste quantity cannot exceed available stock', 'error');
                        return;
                    }

                    if (!reason) {
                        showToast('Please select a reason for all items', 'error');
                        return;
                    }

                    items.push({
                        item_id: parseInt(itemId),
                        quantity: wasteQty,
                        reason: reason,
                        notes: notes || null,
                    });
                }

                if (items.length === 0) {
                    showToast('Please add at least one item to record wastage', 'error');
                    return;
                }

                // Confirmation
                const confirmed = await showConfirmModal(
                    `Are you sure you want to record wastage for <strong>${items.length} item(s)</strong>?<br>
                    <span class="text-red-600 font-bold">This will permanently deduct stock from your inventory.</span>`
                );

                if (!confirmed) return;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Processing...';

                try {
                    const response = await fetch('{{ route("wastage.store") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ items: items }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            document.getElementById('clearFormBtn').click();
                            // Refresh item quantities
                            refreshItemData();
                        }, 1000);
                    } else {
                        showToast(data.message || 'Failed to record wastage', 'error');
                    }
                } catch (err) {
                    showToast('Error recording wastage: ' + err.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Record Wastage';
                }
            });

            // Refresh item data from server
            async function refreshItemData() {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'Accept': 'text/html' }
                    });
                    // Reload page to get fresh data
                    window.location.reload();
                } catch (e) {
                    // Ignore
                }
            }

            // History toggle
            const historyPanel = document.getElementById('historyPanel');
            document.getElementById('toggleHistoryBtn').addEventListener('click', () => {
                historyPanel.classList.toggle('open');
            });

            // Load history
            document.getElementById('loadHistoryBtn').addEventListener('click', async () => {
                const fromDate = document.getElementById('histFromDate').value;
                const toDate = document.getElementById('histToDate').value;

                let url = '{{ route("wastage.history") }}?';
                if (fromDate) url += `from_date=${fromDate}&`;
                if (toDate) url += `to_date=${toDate}&`;

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        }
                    });
                    const data = await response.json();

                    if (data.success) {
                        renderHistory(data.data);
                    }
                } catch (err) {
                    showToast('Failed to load history', 'error');
                }
            });

            function renderHistory(records) {
                const tbody = document.getElementById('historyTableBody');

                if (records.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-400">No wastage records found</td></tr>';
                    return;
                }

                tbody.innerHTML = records.map((r, i) => {
                    const isFG = r.item_type === 'finished_good';
                    const displayQty = isFG ? Math.floor(parseFloat(r.quantity_wasted)) : r.quantity_wasted;
                    const typeBadge = isFG
                        ? '<span style="font-size:10px;background:#dcfce7;color:#166534;padding:1px 6px;border-radius:4px;margin-left:4px;">FG</span>'
                        : '<span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:4px;margin-left:4px;">RM</span>';
                    return `
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="py-2 px-3 text-gray-400">${i + 1}</td>
                        <td class="py-2 px-3 text-gray-600 text-xs">${r.date_display}</td>
                        <td class="py-2 px-3"><span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-mono rounded">${r.wastage_id}</span></td>
                        <td class="py-2 px-3 font-medium text-gray-800">${r.item_name}${typeBadge}</td>
                        <td class="py-2 px-3 text-center text-red-600 font-bold">-${displayQty} ${r.unit}</td>
                        <td class="py-2 px-3 text-center text-red-600 font-semibold">Rs. ${r.wastage_amount}</td>
                        <td class="py-2 px-3"><span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs rounded-full">${r.reason}</span></td>
                        <td class="py-2 px-3 text-gray-500 text-xs">${r.notes}</td>
                    </tr>
                `}).join('');
            }

            // Toast notification
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast');
                toast.textContent = message;
                toast.className = `toast toast-${type} show`;
                setTimeout(() => { toast.classList.remove('show'); }, 4000);
            }

            // Confirmation Modal
            function showConfirmModal(message) {
                return new Promise(resolve => {
                    const modal = document.getElementById('confirmModal');
                    const msgEl = document.getElementById('confirmModalMessage');
                    const okBtn = document.getElementById('confirmModalOk');
                    const cancelBtn = document.getElementById('confirmModalCancel');

                    msgEl.innerHTML = message;
                    modal.style.display = 'flex';

                    function cleanup() {
                        modal.style.display = 'none';
                        okBtn.removeEventListener('click', onOk);
                        cancelBtn.removeEventListener('click', onCancel);
                        modal.removeEventListener('click', onOverlay);
                    }

                    function onOk() { cleanup(); resolve(true); }
                    function onCancel() { cleanup(); resolve(false); }
                    function onOverlay(e) {
                        if (e.target === modal) { cleanup(); resolve(false); }
                    }

                    okBtn.addEventListener('click', onOk);
                    cancelBtn.addEventListener('click', onCancel);
                    modal.addEventListener('click', onOverlay);
                });
            }

            // Expose functions
            window.calculateWastage = window.calculateWastage;
            window.updateTotalWastage = updateTotalWastage;
        });
    </script>
@endpush