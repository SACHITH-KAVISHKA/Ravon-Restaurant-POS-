@extends('layouts.app')

@section('title', 'Update Stock')

@push('styles')
    <style>
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .item-row {
            transition: all 0.2s ease;
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
            padding: 12px 40px 12px 16px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
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
            right: 12px;
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

        /* Ensure parent containers don't clip the dropdown */
        .item-row {
            position: relative;
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
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.15s ease;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
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

        .searchable-dropdown .dropdown-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .searchable-dropdown .clear-btn {
            position: absolute;
            right: 36px;
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
                        Update Stock</h1>
                    <p class="text-gray-500 text-sm">Add multiple items to stock</p>
                </div>
                <a href="{{ route('main-stock.index') }}"
                    class="text-gray-600 hover:text-gray-800 font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Items
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <!-- Stock Update Form -->
                <div>
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        <!-- Form Header -->
                        <div class="px-6 py-4 bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            <h2 class="text-lg font-bold text-white">Add Stock Items</h2>
                            <p class="text-purple-200 text-sm">Select items and enter quantities to add to stock</p>
                        </div>

                        <form id="stockUpdateForm" class="p-6">
                            @csrf

                            <!-- Items Table -->
                            <div class="border border-gray-200 rounded-lg" style="overflow: visible;">
                                <table class="w-full">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200">
                                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Items</th>
                                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700 w-40">Qty
                                            </th>
                                            <th class="text-center py-4 px-4 text-sm font-semibold text-gray-700 w-20">
                                                Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody">
                                        <!-- Initial Row -->
                                        <tr class="item-row border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-4 px-4">
                                                <div class="searchable-dropdown" data-name="items[0][item_id]">
                                                    <input type="hidden" name="items[0][item_id]" class="item-value">
                                                    <input type="text" class="search-input" placeholder="Search or select item..." autocomplete="off">
                                                    <button type="button" class="clear-btn" title="Clear selection">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                    <span class="dropdown-arrow">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </span>
                                                    <div class="dropdown-list"></div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4">
                                                <input type="number" name="items[0][quantity]"
                                                    class="qty-input form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-center"
                                                    step="0.001" min="0.001" placeholder="0.000">
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <button type="button"
                                                    class="delete-row-btn p-3 text-red-500 hover:text-white hover:bg-red-500 rounded-lg transition border border-red-200 hover:border-red-500">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Add Row Button -->
                            <div class="mt-3 flex">
                                <button type="button" id="addRowBtn"
                                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-600 hover:text-white border border-purple-300 hover:bg-gradient-to-r hover:from-[#667eea] hover:to-[#764ba2] hover:border-transparent rounded-lg transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Row
                                </button>
                            </div>

                            <!-- Reference Number & Notes -->
                            <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1">
                                        Reference Number
                                    </label>
                                    <input type="text" name="reference_number" id="reference_number"
                                        class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg"
                                        placeholder="Invoice/PO number (optional)">
                                </div>
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                        Notes
                                    </label>
                                    <input type="text" name="notes" id="notes"
                                        class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg"
                                        placeholder="Additional notes (optional)">
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-100">
                                <button type="button" id="clearFormBtn"
                                    class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                                    Clear Form
                                </button>
                                <button type="submit" id="submitBtn"
                                    class="px-6 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold rounded-lg transition">
                                    Update Stock
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Success/Error Toast -->
    <div id="toast" class="fixed bottom-4 right-4 z-50 hidden">
        <div id="toastContent" class="px-6 py-3 rounded-lg shadow-lg font-medium"></div>
    </div>

    <!-- Items Data for JavaScript -->
    <script type="application/json" id="items-data">
        @php
            $itemsArray = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                    'unit' => $item->unit_abbreviation
                ];
            })->values();
            echo json_encode($itemsArray);
        @endphp
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('stockUpdateForm');
            const tableBody = document.getElementById('itemsTableBody');
            const clearFormBtn = document.getElementById('clearFormBtn');
            let rowIndex = 1;

            // Items data for dropdown
            const itemsData = JSON.parse(document.getElementById('items-data').textContent);

            // Get selected item IDs (excluding the given dropdown)
            function getSelectedItemIds(excludeDropdown = null) {
                const ids = [];
                document.querySelectorAll('.searchable-dropdown').forEach(dropdown => {
                    if (dropdown !== excludeDropdown) {
                        const value = dropdown.querySelector('.item-value').value;
                        if (value) {
                            ids.push(parseInt(value));
                        }
                    }
                });
                return ids;
            }

            // Filter items based on search query and exclude already selected items
            function getFilteredItems(searchQuery, excludeDropdown = null) {
                const selectedIds = getSelectedItemIds(excludeDropdown);
                const query = searchQuery.toLowerCase().trim();
                
                return itemsData.filter(item => {
                    // Exclude already selected items
                    if (selectedIds.includes(item.id)) return false;
                    
                    // If no search query, show all available items
                    if (!query) return true;
                    
                    // Match against code or name
                    return item.code.toLowerCase().includes(query) || 
                           item.name.toLowerCase().includes(query);
                });
            }

            // Render dropdown items
            function renderDropdownItems(dropdown, items, highlightIndex = -1) {
                const list = dropdown.querySelector('.dropdown-list');
                const currentValue = dropdown.querySelector('.item-value').value;
                
                if (items.length === 0) {
                    list.innerHTML = '<div class="no-results">No items found</div>';
                    return;
                }
                
                list.innerHTML = items.map((item, index) => `
                    <div class="dropdown-item${item.id == currentValue ? ' selected' : ''}${index === highlightIndex ? ' highlighted' : ''}" 
                         data-id="${item.id}" 
                         data-code="${item.code}" 
                         data-name="${item.name}"
                         data-unit="${item.unit}">
                        <span class="item-code">${item.code}</span> - <span class="item-name">${item.name}</span>
                    </div>
                `).join('');
            }

            // Initialize searchable dropdown
            function initSearchableDropdown(dropdown) {
                const searchInput = dropdown.querySelector('.search-input');
                const hiddenInput = dropdown.querySelector('.item-value');
                const clearBtn = dropdown.querySelector('.clear-btn');
                const list = dropdown.querySelector('.dropdown-list');
                let highlightedIndex = -1;
                let currentItems = [];

                // Click on input to open dropdown
                searchInput.addEventListener('click', function() {
                    openDropdown();
                });

                // Focus on input to open dropdown
                searchInput.addEventListener('focus', function() {
                    openDropdown();
                });

                // Input for searching
                searchInput.addEventListener('input', function() {
                    const query = this.value;
                    currentItems = getFilteredItems(query, dropdown);
                    highlightedIndex = -1;
                    renderDropdownItems(dropdown, currentItems, highlightedIndex);
                    
                    if (!dropdown.classList.contains('open')) {
                        dropdown.classList.add('open');
                    }
                });

                // Keyboard navigation
                searchInput.addEventListener('keydown', function(e) {
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

                // Clear button
                clearBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    clearSelection();
                });

                // Click on dropdown item
                list.addEventListener('click', function(e) {
                    const item = e.target.closest('.dropdown-item');
                    if (item) {
                        const itemData = {
                            id: item.dataset.id,
                            code: item.dataset.code,
                            name: item.dataset.name,
                            unit: item.dataset.unit
                        };
                        selectItem(itemData);
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
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
                    
                    // Restore display text if there's a selection
                    if (hiddenInput.value) {
                        const item = itemsData.find(i => i.id == hiddenInput.value);
                        if (item) {
                            searchInput.value = `${item.code} - ${item.name}`;
                        }
                    }
                }

                function selectItem(itemData) {
                    hiddenInput.value = itemData.id;
                    searchInput.value = `${itemData.code} - ${itemData.name}`;
                    dropdown.classList.add('has-value');
                    closeDropdown();
                    
                    // Update all dropdowns to exclude this item
                    updateAllDropdowns();
                    
                    // Focus on quantity input
                    const row = dropdown.closest('.item-row');
                    if (row) {
                        const qtyInput = row.querySelector('.qty-input');
                        if (qtyInput) {
                            qtyInput.focus();
                        }
                    }
                }

                function clearSelection() {
                    hiddenInput.value = '';
                    searchInput.value = '';
                    dropdown.classList.remove('has-value');
                    updateAllDropdowns();
                    searchInput.focus();
                }

                function scrollToHighlighted(list, index) {
                    const items = list.querySelectorAll('.dropdown-item');
                    if (items[index]) {
                        items[index].scrollIntoView({ block: 'nearest' });
                    }
                }
            }

            // Update all dropdowns (refresh their available items)
            function updateAllDropdowns() {
                document.querySelectorAll('.searchable-dropdown').forEach(dropdown => {
                    // The dropdown will automatically exclude selected items when opened
                    // No need to rebuild here
                });
            }

            // Get searchable dropdown HTML
            function getSearchableDropdownHtml(index) {
                return `
                    <div class="searchable-dropdown" data-name="items[${index}][item_id]">
                        <input type="hidden" name="items[${index}][item_id]" class="item-value">
                        <input type="text" class="search-input" placeholder="Search or select item..." autocomplete="off">
                        <button type="button" class="clear-btn" title="Clear selection">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <span class="dropdown-arrow">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                        <div class="dropdown-list"></div>
                    </div>
                `;
            }

            // Add new row
            function addNewRow() {
                const newRow = document.createElement('tr');
                newRow.className = 'item-row border-b border-gray-100 hover:bg-gray-50';
                newRow.innerHTML = `
                    <td class="py-4 px-4">
                        ${getSearchableDropdownHtml(rowIndex)}
                    </td>
                    <td class="py-4 px-4">
                        <input type="number" name="items[${rowIndex}][quantity]" class="qty-input form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-center" step="0.001" min="0.001" placeholder="0.000">
                    </td>
                    <td class="py-4 px-4 text-center">
                        <button type="button" class="delete-row-btn p-3 text-red-500 hover:text-white hover:bg-red-500 rounded-lg transition border border-red-200 hover:border-red-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                `;
                tableBody.appendChild(newRow);
                rowIndex++;

                // Initialize the new dropdown
                const newDropdown = newRow.querySelector('.searchable-dropdown');
                initSearchableDropdown(newDropdown);

                // Focus on the new search input
                newDropdown.querySelector('.search-input').focus();

                // Add event listeners
                attachRowEventListeners(newRow);
            }

            // Attach event listeners to row elements
            function attachRowEventListeners(row) {
                const qtyInput = row.querySelector('.qty-input');
                const deleteBtn = row.querySelector('.delete-row-btn');
                const dropdown = row.querySelector('.searchable-dropdown');

                // TAB on quantity input adds new row
                qtyInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab' && !e.shiftKey) {
                        const itemValue = dropdown.querySelector('.item-value').value;
                        const qtyValue = this.value;

                        // Only add new row if current row has valid data
                        if (itemValue && qtyValue && parseFloat(qtyValue) > 0) {
                            // Check if this is the last row
                            const rows = tableBody.querySelectorAll('.item-row');
                            const isLastRow = rows[rows.length - 1] === row;

                            if (isLastRow) {
                                e.preventDefault();
                                addNewRow();
                            }
                        }
                    }
                });

                // Delete row
                deleteBtn.addEventListener('click', function() {
                    const rows = tableBody.querySelectorAll('.item-row');
                    if (rows.length > 1) {
                        row.remove();
                        updateAllDropdowns();
                    } else {
                        // Clear the row instead of deleting if it's the last one
                        dropdown.querySelector('.item-value').value = '';
                        dropdown.querySelector('.search-input').value = '';
                        dropdown.classList.remove('has-value');
                        qtyInput.value = '';
                        updateAllDropdowns();
                    }
                });
            }

            // Clear form
            clearFormBtn.addEventListener('click', function() {
                // Remove all rows except the first
                const rows = tableBody.querySelectorAll('.item-row');
                rows.forEach((row, index) => {
                    if (index > 0) {
                        row.remove();
                    } else {
                        const dropdown = row.querySelector('.searchable-dropdown');
                        dropdown.querySelector('.item-value').value = '';
                        dropdown.querySelector('.search-input').value = '';
                        dropdown.classList.remove('has-value');
                        row.querySelector('.qty-input').value = '';
                    }
                });
                document.getElementById('reference_number').value = '';
                document.getElementById('notes').value = '';
                rowIndex = 1;
                updateAllDropdowns();
            });

            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Collect items data
                const items = [];
                document.querySelectorAll('.item-row').forEach(row => {
                    const itemId = row.querySelector('.item-value').value;
                    const qty = row.querySelector('.qty-input').value;
                    if (itemId && qty && parseFloat(qty) > 0) {
                        items.push({
                            item_id: parseInt(itemId),
                            quantity: parseFloat(qty)
                        });
                    }
                });

                if (items.length === 0) {
                    showToast('Please add at least one item with quantity', 'error');
                    return;
                }

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';

                try {
                    const response = await fetch('{{ route("main-stock.process-stock-update") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            items: items,
                            reference_number: document.getElementById('reference_number').value,
                            notes: document.getElementById('notes').value,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');

                        // Clear form after successful submission
                        clearFormBtn.click();
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    showToast('An error occurred. Please try again.', 'error');
                    console.error('Error:', error);
                }

                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Update Stock';
            });

            // Add Row button
            document.getElementById('addRowBtn').addEventListener('click', function() {
                addNewRow();
            });

            // Initialize all existing dropdowns
            document.querySelectorAll('.searchable-dropdown').forEach(dropdown => {
                initSearchableDropdown(dropdown);
            });

            // Attach event listeners to initial row
            document.querySelectorAll('.item-row').forEach(row => {
                attachRowEventListeners(row);
            });

            function showToast(message, type) {
                const toast = document.getElementById('toast');
                const toastContent = document.getElementById('toastContent');

                toastContent.textContent = message;
                toastContent.className = 'px-6 py-3 rounded-lg shadow-lg font-medium ' +
                    (type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');

                toast.classList.remove('hidden');

                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 3000);
            }
        });
    </script>
@endsection
