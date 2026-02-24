@extends('layouts.app')

@section('title', 'Stock Adjustment')

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
            background-color: #f9fafb;
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
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.15);
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

        /* Variance coloring */
        .var-positive {
            color: #059669;
            font-weight: 600;
        }

        .var-negative {
            color: #dc2626;
            font-weight: 600;
        }

        .var-zero {
            color: #6b7280;
        }

        /* Total variance bar */
        .total-variance-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 16px 24px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Readonly input styling */
        input[readonly] {
            background-color: #f1f5f9 !important;
            cursor: default;
        }

        /* Price field - indigo tint */
        .price-input {
            background-color: #eef2ff !important;
            border-color: #c7d2fe !important;
            color: #3730a3 !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            letter-spacing: 0.01em;
        }

        /* System Qty field - slate tint */
        .system-qty-input {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #1e293b !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
        }

        /* Actual Qty - editable, stand-out */
        .actual-qty-input {
            background-color: #fff !important;
            color: #1e293b !important;
            font-weight: 800 !important;
            font-size: 0.92rem !important;
        }

        /* Var Qty / Var Amount */
        .var-qty-input,
        .var-amount-input {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
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
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .category-select:hover {
            border-color: #667eea;
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
                        Stock Adjustment</h1>
                    <p class="text-gray-500 text-sm">Count & adjust RM and Cashier stock levels</p>
                </div>
                <a href="{{ route('stock-adjustment.history') }}"
                    class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 hover:bg-purple-50 hover:text-purple-600 hover:border-purple-200 font-medium rounded-lg transition flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Adjustment History
                </a>
            </div>

            <!-- Adjustment Details Card -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                    <h2 class="text-lg font-bold text-white">Adjustment Details</h2>
                    <p class="text-purple-200 text-sm">Fill in the adjustment information</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Date & Time -->
                        <div>
                            <label for="adjustment_date" class="block text-sm font-semibold text-gray-700 mb-1">
                                Date & Time <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" id="adjustment_date"
                                class="form-input w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-medium"
                                required>
                        </div>
                        <!-- Cashier Name -->
                        <div>
                            <label for="cashier_name" class="block text-sm font-semibold text-gray-700 mb-1">
                                Cashier Name
                            </label>
                            <input type="text" id="cashier_name"
                                class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm"
                                placeholder="Cashier Name">
                        </div>
                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">
                                Notes
                            </label>
                            <textarea id="notes" rows="1"
                                class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm resize-none"
                                placeholder="Additional notes (optional)"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adjustment Items Table -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-6">
                <div
                    class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Adjustment Items</h2>
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
                        <table class="w-full" id="adjustmentTable">
                            <thead>
                                <tr class="bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200">
                                    <th class="text-left py-3 px-3 text-xs font-semibold text-gray-700 uppercase"
                                        style="min-width: 180px; max-width: 320px;">Item</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-indigo-700 uppercase w-20">
                                        Price</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-gray-700 uppercase w-20">
                                        System Qty</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-purple-700 uppercase w-24">
                                        Actual Qty</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-gray-700 uppercase w-20">Var
                                        Qty</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-gray-700 uppercase w-24">Var
                                        Amount</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-gray-700 uppercase w-14">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Initial empty row -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total Variance -->
                <div class="px-6 pb-6">
                    <div class="total-variance-bar" id="totalVarianceBar">
                        <span>Total Variance Amount</span>
                        <span id="totalVarianceAmount">Rs. 0.00</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 px-6 pb-6">
                    <button type="button" id="clearFormBtn"
                        class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                        Clear Form
                    </button>
                    <button type="button" id="submitBtn"
                        class="px-6 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold rounded-lg transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Adjustment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal-overlay" style="display:none;">
        <div class="confirm-modal-box">
            <div class="confirm-modal-icon">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="confirm-modal-title">Confirm Stock Adjustment</h3>
            <p id="confirmModalMessage" class="confirm-modal-msg"></p>
            <div class="confirm-modal-actions">
                <button type="button" id="confirmModalCancel" class="confirm-modal-btn cancel">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Cancel
                </button>
                <button type="button" id="confirmModalOk" class="confirm-modal-btn ok">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Yes, Save
                </button>
            </div>
        </div>
    </div>

    <style>
        .confirm-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .confirm-modal-box {
            background: #fff;
            border-radius: 20px;
            padding: 36px 32px 28px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(99, 102, 241, 0.25), 0 0 0 1px rgba(99, 102, 241, 0.08);
            text-align: center;
            animation: scaleIn 0.25s ease;
        }

        .confirm-modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            color: #fff;
        }

        .confirm-modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e1b4b;
            margin-bottom: 8px;
            font-family: 'Inter', sans-serif;
        }

        .confirm-modal-msg {
            font-size: 0.92rem;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .confirm-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .confirm-modal-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .confirm-modal-btn.cancel {
            background: #f3f4f6;
            color: #4b5563;
        }

        .confirm-modal-btn.cancel:hover {
            background: #e5e7eb;
            color: #1f2937;
        }

        .confirm-modal-btn.ok {
            background: linear-gradient(135deg, #7c3aed, #6366f1);
            color: #fff;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .confirm-modal-btn.ok:hover {
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.55);
            transform: translateY(-1px);
        }
    </style>

    <!-- Items Data for JavaScript -->
    <script type="application/json" id="items-data">
                                {!! json_encode($items) !!}
                            </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.getElementById('itemsTableBody');
            const clearFormBtn = document.getElementById('clearFormBtn');
            const submitBtn = document.getElementById('submitBtn');
            let rowIndex = 0;

            // ── Event Delegation REMOVED (Handled Inline) ──

            // Handle Tab to Add Row
            tableBody.addEventListener('keydown', function (e) {
                if (e.key === 'Tab' && !e.shiftKey && e.target.classList.contains('actual-qty-input')) {
                    const row = e.target.closest('.item-row');
                    const dropdown = row.querySelector('.searchable-dropdown');
                    const itemValue = dropdown.querySelector('.item-value').value;
                    const qtyValue = e.target.value;

                    if (itemValue && qtyValue !== '') {
                        const rows = tableBody.querySelectorAll('.item-row');
                        const isLastRow = rows[rows.length - 1] === row;
                        if (isLastRow) {
                            e.preventDefault();
                            addNewRow();
                        }
                    }
                }
            });

            // Handle Delete Row
            tableBody.addEventListener('click', function (e) {
                const deleteBtn = e.target.closest('.delete-row-btn');
                if (deleteBtn) {
                    const row = deleteBtn.closest('.item-row');
                    const rows = tableBody.querySelectorAll('.item-row');
                    if (rows.length > 1) {
                        row.remove();
                        updateTotalVariance();
                    } else {
                        // Clear row if it's the only one
                        const dropdown = row.querySelector('.searchable-dropdown');
                        dropdown.querySelector('.item-value').value = '';
                        dropdown.querySelector('.search-input').value = '';
                        dropdown.classList.remove('has-value');
                        row.querySelector('.price-input').value = '';
                        row.querySelector('.system-qty-input').value = '';
                        row.querySelector('.actual-qty-input').value = '';
                        row.querySelector('.var-qty-input').value = '';
                        row.querySelector('.var-amount-input').value = '';
                        delete row.dataset.price;
                        delete row.dataset.systemQty;
                        delete row.dataset.stockLocation;
                        delete row.dataset.itemType;
                        updateTotalVariance();
                    }
                }
            });

            // Items data
            const itemsData = JSON.parse(document.getElementById('items-data').textContent);

            // Set default date/time to now
            const now = new Date();
            const offset = now.getTimezoneOffset();
            const localDate = new Date(now.getTime() - offset * 60 * 1000);
            document.getElementById('adjustment_date').value = localDate.toISOString().slice(0, 16);

            // Add initial row
            addNewRow();

            // ── Dropdown Utilities ──
            function getSelectedItemIds(excludeDropdown = null) {
                const ids = [];
                document.querySelectorAll('.searchable-dropdown').forEach(dropdown => {
                    if (dropdown !== excludeDropdown) {
                        const value = dropdown.querySelector('.item-value').value;
                        if (value) ids.push(parseInt(value));
                    }
                });
                return ids;
            }

            // Category filter
            const categoryFilter = document.getElementById('categoryFilter');
            let selectedCategory = 'all';

            categoryFilter.addEventListener('change', function () {
                selectedCategory = this.value;
            });

            function getFilteredItems(searchQuery, excludeDropdown = null) {
                const selectedIds = getSelectedItemIds(excludeDropdown);
                const query = searchQuery.toLowerCase().trim();
                return itemsData.filter(item => {
                    if (selectedIds.includes(item.id)) return false;
                    // Category filter
                    if (selectedCategory !== 'all' && item.type !== selectedCategory) return false;
                    if (!query) return true;
                    return item.code.toLowerCase().includes(query) ||
                        item.name.toLowerCase().includes(query);
                });
            }

            function renderDropdownItems(dropdown, items, highlightIndex = -1) {
                const list = dropdown.querySelector('.dropdown-list');
                const currentValue = dropdown.querySelector('.item-value').value;
                if (items.length === 0) {
                    list.innerHTML = '<div class="no-results">No items found</div>';
                    return;
                }
                list.innerHTML = items.map((item, index) => {
                    const typeBadge = item.type === 'finished_good'
                        ? '<span class="item-type-badge bg-blue-100 text-blue-700">FG</span>'
                        : '<span class="item-type-badge bg-amber-100 text-amber-700">RM</span>';
                    return `
                                                <div class="dropdown-item${item.id == currentValue ? ' selected' : ''}${index === highlightIndex ? ' highlighted' : ''}"
                                                     data-id="${item.id}"
                                                     data-code="${item.code}"
                                                     data-name="${item.name}"
                                                     data-type="${item.type}"
                                                     data-unit="${item.unit}"
                                                     data-price="${item.price}"
                                                     data-system-qty="${item.system_qty}"
                                                     data-stock-location="${item.stock_location}">
                                                    <span class="item-code">${item.code}</span> - <span class="item-name">${item.name}</span>
                                                    ${typeBadge}
                                                </div>
                                            `;
                }).join('');
            }

            // ── Init Searchable Dropdown ──
            function initSearchableDropdown(dropdown) {
                const searchInput = dropdown.querySelector('.search-input');
                const hiddenInput = dropdown.querySelector('.item-value');
                const clearBtn = dropdown.querySelector('.clear-btn');
                const list = dropdown.querySelector('.dropdown-list');
                let highlightedIndex = -1;
                let currentItems = [];

                searchInput.addEventListener('click', openDropdown);
                searchInput.addEventListener('focus', openDropdown);

                searchInput.addEventListener('input', function () {
                    if (this.value.trim() === '') {
                        clearSelection();
                    }
                    currentItems = getFilteredItems(this.value, dropdown);
                    highlightedIndex = -1;
                    renderDropdownItems(dropdown, currentItems, highlightedIndex);
                    if (!dropdown.classList.contains('open')) {
                        dropdown.classList.add('open');
                    }
                });

                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (!dropdown.classList.contains('open')) {
                            openDropdown();
                        } else {
                            highlightedIndex = Math.min(highlightedIndex + 1, currentItems.length - 1);
                            renderDropdownItems(dropdown, currentItems, highlightedIndex);
                            scrollToHighlighted(list, highlightedIndex);
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        highlightedIndex = Math.max(highlightedIndex - 1, 0);
                        renderDropdownItems(dropdown, currentItems, highlightedIndex);
                        scrollToHighlighted(list, highlightedIndex);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (highlightedIndex >= 0 && currentItems[highlightedIndex]) {
                            selectItem(currentItems[highlightedIndex]);
                        } else if (currentItems.length === 1) {
                            selectItem(currentItems[0]);
                        }
                    } else if (e.key === 'Escape') {
                        closeDropdown();
                    } else if (e.key === 'Tab') {
                        closeDropdown();
                    }
                });

                clearBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    clearSelection();
                });

                list.addEventListener('click', function (e) {
                    const item = e.target.closest('.dropdown-item');
                    if (item) {
                        selectItem({
                            id: item.dataset.id,
                            code: item.dataset.code,
                            name: item.dataset.name,
                            type: item.dataset.type,
                            unit: item.dataset.unit,
                            price: parseFloat(item.dataset.price),
                            system_qty: parseFloat(item.dataset.systemQty),
                            stock_location: item.dataset.stockLocation,
                        });
                    }
                });

                document.addEventListener('click', function (e) {
                    if (!dropdown.contains(e.target)) {
                        closeDropdown();
                    }
                });

                function openDropdown() {
                    currentItems = getFilteredItems(searchInput.value, dropdown);
                    renderDropdownItems(dropdown, currentItems, highlightedIndex);
                    dropdown.classList.add('open');
                }

                function closeDropdown() {
                    dropdown.classList.remove('open');
                    highlightedIndex = -1;
                    if (hiddenInput.value) {
                        const item = itemsData.find(i => i.id == hiddenInput.value);
                        if (item) searchInput.value = `${item.code} - ${item.name}`;
                    }
                }

                function selectItem(itemData) {
                    hiddenInput.value = itemData.id;
                    searchInput.value = `${itemData.code} - ${itemData.name}`;
                    dropdown.classList.add('has-value');
                    closeDropdown();

                    // Auto-fill price, system qty
                    const row = dropdown.closest('.item-row');
                    const price = parseFloat(itemData.price);
                    const systemQty = parseFloat(itemData.system_qty);
                    row.querySelector('.price-input').value = price.toFixed(2);
                    row.querySelector('.system-qty-input').value = systemQty.toFixed(3);
                    row.dataset.stockLocation = itemData.stock_location;
                    row.dataset.itemType = itemData.type;
                    row.dataset.price = price;
                    row.dataset.systemQty = systemQty;

                    // Clear actual qty and variance
                    row.querySelector('.actual-qty-input').value = '';
                    row.querySelector('.var-qty-input').value = '';
                    row.querySelector('.var-amount-input').value = '';

                    // Focus on actual qty
                    row.querySelector('.actual-qty-input').focus();
                    updateTotalVariance();
                }

                function clearSelection() {
                    hiddenInput.value = '';
                    searchInput.value = '';
                    dropdown.classList.remove('has-value');
                    const row = dropdown.closest('.item-row');
                    row.querySelector('.price-input').value = '';
                    row.querySelector('.system-qty-input').value = '';
                    row.querySelector('.actual-qty-input').value = '';
                    row.querySelector('.var-qty-input').value = '';
                    row.querySelector('.var-amount-input').value = '';
                    delete row.dataset.price;
                    delete row.dataset.systemQty;
                    delete row.dataset.stockLocation;
                    delete row.dataset.itemType;
                    updateTotalVariance();
                    searchInput.focus();
                }

                function scrollToHighlighted(list, index) {
                    const items = list.querySelectorAll('.dropdown-item');
                    if (items[index]) items[index].scrollIntoView({ block: 'nearest' });
                }
            }

            // ── Row HTML ──
            function getRowHtml(index) {
                return `
                                            <tr class="item-row border-b border-gray-100 hover:bg-gray-50" data-index="${index}">
                                                <td class="py-2 px-2" style="min-width:180px; max-width:320px;">
                                                    <div class="searchable-dropdown" data-name="items[${index}][item_id]">
                                                        <input type="hidden" name="items[${index}][item_id]" class="item-value">
                                                        <input type="text" class="search-input" placeholder="🔍 Search item..." autocomplete="off" style="padding: 7px 36px 7px 10px; font-size:12.5px;">
                                                        <button type="button" class="clear-btn" title="Clear selection">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                        <span class="dropdown-arrow">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                            </svg>
                                                        </span>
                                                        <div class="dropdown-list"></div>
                                                    </div>
                                                </td>
                                                <td class="py-2 px-1.5">
                                                    <input type="text" class="price-input form-input w-full px-2 py-2 border rounded-lg text-center" readonly tabindex="-1" placeholder="—">
                                                </td>
                                                <td class="py-2 px-1.5">
                                                    <input type="text" class="system-qty-input form-input w-full px-2 py-2 border rounded-lg text-center" readonly tabindex="-1" placeholder="—">
                                                </td>
                                                <td class="py-2 px-1.5">
                                                    <input type="number" class="actual-qty-input form-input w-full px-2 py-2 border-2 border-purple-300 rounded-lg text-center focus:border-purple-500 focus:ring-2 focus:ring-purple-200" step="0.001" min="0" placeholder="0.000"
                                                    oninput="calculateVariance(this.closest('.item-row')); updateTotalVariance()"
                                                    onkeyup="calculateVariance(this.closest('.item-row')); updateTotalVariance()"
                                                    onchange="calculateVariance(this.closest('.item-row')); updateTotalVariance()">
                                                </td>
                                                <td class="py-2 px-1.5">
                                                    <input type="text" class="var-qty-input form-input w-full px-2 py-2 border rounded-lg text-center" readonly tabindex="-1" placeholder="—">
                                                </td>
                                                <td class="py-2 px-1.5">
                                                    <input type="text" class="var-amount-input form-input w-full px-2 py-2 border rounded-lg text-center" readonly tabindex="-1" placeholder="—">
                                                </td>
                                                <td class="py-2 px-2 text-center">
                                                    <button type="button"
                                                        class="delete-row-btn p-2 text-red-500 hover:text-white hover:bg-red-500 rounded-lg transition border border-red-200 hover:border-red-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        `;
            }

            // ── Add New Row ──
            function addNewRow() {
                const newRow = document.createElement('tbody');
                newRow.innerHTML = getRowHtml(rowIndex);
                const tr = newRow.firstElementChild;
                tableBody.appendChild(tr);
                rowIndex++;

                const dropdown = tr.querySelector('.searchable-dropdown');
                initSearchableDropdown(dropdown);
                // Listeners handled by delegation

                dropdown.querySelector('.search-input').focus();
                return tr;
            }



            // ── Calculate Variance ──
            function calculateVariance(row) {
                // Try dataset first (most accurate), fallback to input value parsing
                let systemQty = parseFloat(row.dataset.systemQty);
                if (isNaN(systemQty)) systemQty = parseFloat(row.querySelector('.system-qty-input').value) || 0;

                let price = parseFloat(row.dataset.price);
                if (isNaN(price)) price = parseFloat(row.querySelector('.price-input').value) || 0;

                const actualQtyInput = row.querySelector('.actual-qty-input');
                let actualQtyVal = actualQtyInput.value;
                let actualQty = parseFloat(actualQtyVal);

                // Even if input is partial, we try to calculate if valid number
                // If completely empty, we clear variance
                if (actualQtyVal === '' || isNaN(actualQty)) {
                    row.querySelector('.var-qty-input').value = '';
                    row.querySelector('.var-amount-input').value = '';
                    return;
                }

                const varQty = actualQty - systemQty;
                const varAmount = varQty * price;

                const varQtyInput = row.querySelector('.var-qty-input');
                const varAmountInput = row.querySelector('.var-amount-input');

                varQtyInput.value = (varQty >= 0 ? '+' : '') + varQty.toFixed(3);
                varAmountInput.value = (varAmount >= 0 ? '+' : '') + varAmount.toFixed(2);

                // Color coding — only remove color classes, NOT element identifier classes
                varQtyInput.classList.remove('var-positive', 'var-negative', 'var-zero');
                varAmountInput.classList.remove('var-positive', 'var-negative', 'var-zero');

                if (varQty > 0) {
                    varQtyInput.classList.add('var-positive');
                    varAmountInput.classList.add('var-positive');
                } else if (varQty < 0) {
                    varQtyInput.classList.add('var-negative');
                    varAmountInput.classList.add('var-negative');
                } else {
                    varQtyInput.classList.add('var-zero');
                    varAmountInput.classList.add('var-zero');
                }
            }

            // ── Update Total Variance ──
            function updateTotalVariance() {
                let total = 0;
                document.querySelectorAll('.var-amount-input').forEach(input => {
                    const val = parseFloat(input.value.replace(/[^\d.-]/g, ''));
                    if (!isNaN(val)) total += val;
                });

                const display = document.getElementById('totalVarianceAmount');
                const bar = document.getElementById('totalVarianceBar');
                const sign = total >= 0 ? '+' : '';
                display.textContent = `Rs. ${sign}${total.toFixed(2)}`;

                // Color the bar
                bar.style.background = total > 0
                    ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)'
                    : total < 0
                        ? 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)'
                        : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }

            // ── Clear Form ──
            clearFormBtn.addEventListener('click', function () {
                const rows = tableBody.querySelectorAll('.item-row');
                rows.forEach((row, index) => {
                    if (index > 0) {
                        row.remove();
                    } else {
                        const dropdown = row.querySelector('.searchable-dropdown');
                        dropdown.querySelector('.item-value').value = '';
                        dropdown.querySelector('.search-input').value = '';
                        dropdown.classList.remove('has-value');
                        row.querySelector('.price-input').value = '';
                        row.querySelector('.system-qty-input').value = '';
                        row.querySelector('.actual-qty-input').value = '';
                        row.querySelector('.var-qty-input').value = '';
                        row.querySelector('.var-amount-input').value = '';
                        delete row.dataset.price;
                        delete row.dataset.systemQty;
                        delete row.dataset.stockLocation;
                        delete row.dataset.itemType;
                    }
                });
                document.getElementById('cashier_name').value = '';
                document.getElementById('notes').value = '';
                rowIndex = 1;
                updateTotalVariance();
            });

            // ── Submit ──
            submitBtn.addEventListener('click', async function () {
                const adjustmentDate = document.getElementById('adjustment_date').value;
                if (!adjustmentDate) {
                    showToast('Please select a date & time', 'error');
                    return;
                }

                const items = [];
                document.querySelectorAll('.item-row').forEach(row => {
                    const itemId = row.querySelector('.item-value').value;
                    const actualQty = row.querySelector('.actual-qty-input').value;
                    if (itemId && actualQty !== '') {
                        items.push({
                            item_id: parseInt(itemId),
                            actual_qty: parseFloat(actualQty),
                        });
                    }
                });

                if (items.length === 0) {
                    showToast('Please add at least one item with actual quantity', 'error');
                    return;
                }

                // Confirm via modal
                const confirmed = await showConfirmModal(`Are you sure you want to save this stock adjustment for <strong>${items.length} item(s)</strong>? This will update the stock levels.`);
                if (!confirmed) return;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';

                try {
                    const response = await fetch('{{ route("stock-adjustment.store") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            adjustment_date: adjustmentDate,
                            cashier_name: document.getElementById('cashier_name').value,
                            notes: document.getElementById('notes').value,
                            items: items,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');
                        // Reset form after short delay
                        setTimeout(() => {
                            clearFormBtn.click();
                            // Reset date
                            const now2 = new Date();
                            const offset2 = now2.getTimezoneOffset();
                            const localDate2 = new Date(now2.getTime() - offset2 * 60 * 1000);
                            document.getElementById('adjustment_date').value = localDate2.toISOString().slice(0, 16);
                        }, 1000);
                    } else {
                        showToast(data.message || 'Failed to save adjustment', 'error');
                    }
                } catch (err) {
                    showToast('Error saving adjustment: ' + err.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Save Adjustment';
                }
            });

            // ── Confirmation Modal ──
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

            // Expose functions to global scope for inline event handlers
            window.calculateVariance = calculateVariance;
            window.updateTotalVariance = updateTotalVariance;
        });
    </script>
@endsection