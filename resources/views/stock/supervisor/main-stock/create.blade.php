@extends('layouts.app')

@section('title', 'Add New Stock Items')

@push('styles')
    <style>
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            background: linear-gradient(to right, #f3e8ff, #e0e7ff);
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        .items-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .items-table tr:hover {
            background-color: #faf5ff;
        }

        .items-table input,
        .items-table select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .items-table input:focus,
        .items-table select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .items-table .item-code-input {
            font-family: monospace;
            background-color: #f9fafb;
        }

        .remove-row-btn {
            padding: 6px;
            color: #ef4444;
            background: #fee2e2;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .remove-row-btn:hover {
            background: #fecaca;
            color: #dc2626;
        }

        .remove-row-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .add-row-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .add-row-btn:hover {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transform: translateY(-1px);
        }

        .row-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 600;
        }

        .error-text {
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .hint-text {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 8px;
        }

        .linked-item-cell {
            color: #9ca3af;
            font-style: italic;
        }

        .linked-item-cell .linked-item-select {
            display: none;
        }

        .linked-item-cell.show .linked-item-select {
            display: block;
        }

        .linked-item-cell.show .na-text {
            display: none;
        }

        .linked-item-select {
            background-color: #fef3c7;
            border-color: #f59e0b !important;
        }

        .linked-item-select:focus {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
        }

        .finished-goods-badge {
            display: inline-block;
            padding: 2px 8px;
            background: linear-gradient(to right, #fbbf24, #f59e0b);
            color: white;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 4px;
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
                        Add New Stock Items</h1>
                    <p class="text-gray-500 text-sm">Create one or more items in your main stock inventory</p>
                </div>
                <a href="{{ route('main-stock.index') }}"
                    class="text-gray-600 hover:text-gray-800 font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>
            </div>

            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div
                    class="p-6 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Item Details</h2>
                    </div>
                    <button type="button" onclick="addNewRow()" class="add-row-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Row
                    </button>
                </div>

                <form action="{{ route('main-stock.store') }}" method="POST" id="stockForm">
                    @csrf

                    <div class="table-container">
                        <table class="items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 110px;">Item Code <span class="text-red-500">*</span></th>
                                    <th style="width: 150px;">Item Name <span class="text-red-500">*</span></th>
                                    <th style="width: 180px;">Linked Menu Item</th>
                                    <th style="width: 130px;">Item Type <span class="text-red-500">*</span></th>
                                    <th style="width: 120px;">Unit <span class="text-red-500">*</span></th>
                                    <th style="width: 100px;">Normalization</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Rows will be added here dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-3 p-6 border-t border-gray-100">
                        <a href="{{ route('main-stock.index') }}"
                            class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold rounded-lg transition">
                            Create Stock Items
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Item types and unit types from PHP
        const itemTypes = @json(\App\Models\MainStockItem::ITEM_TYPES);
        const unitTypes = @json(\App\Models\MainStockItem::UNIT_TYPES);

        // Finished goods items from PHP (for linked item dropdown)
        const finishedGoodsItems = [];
        @foreach($finishedGoodsItems as $fgItem)
            finishedGoodsItems.push({
                id: {{ $fgItem->id }},
                name: @json($fgItem->name),
                category: @json($fgItem->category->name ?? 'N/A'),
                portions: [
                    @foreach($fgItem->activeModifiers as $portion)
                                                                                    {
                            id: {{ $portion->id }},
                            name: @json($portion->name),
                            fullName: @json($fgItem->name . ' - ' . $portion->name)
                        },
                    @endforeach
                                                        ]
            });
        @endforeach

        let rowCounter = 0;

        // Track all used codes across the form to prevent duplicates
        const usedCodes = new Set();

        // Calculate offset by finding the highest code number used for each type prefix
        function getCodeOffset(type) {
            const prefix = {
                'raw_material': 'RM',
                'finished_good': 'FG',
                'other': 'OT'
            }[type] || 'OT';

            // Find the highest number used for this prefix in the form
            const codeInputs = document.querySelectorAll('.item-code-input');
            let maxNumber = 0;
            let count = 0;

            codeInputs.forEach(input => {
                if (input.value && input.value.startsWith(prefix)) {
                    count++;
                    // Extract the number part (e.g., "RM00005" -> 5)
                    const numPart = parseInt(input.value.substring(2), 10);
                    if (!isNaN(numPart) && numPart > maxNumber) {
                        maxNumber = numPart;
                    }
                }
            });

            // Return offset to ensure next code is higher than max used
            // The offset accounts for codes already in the database + codes in the form
            return count > 0 ? count : 0;
        }

        // Check if a code is already used in the form
        function isCodeUsedInForm(code) {
            const codeInputs = document.querySelectorAll('.item-code-input');
            for (const input of codeInputs) {
                if (input.value === code) {
                    return true;
                }
            }
            return false;
        }

        // Generate item type options HTML
        function getItemTypeOptions(selectedValue = 'raw_material') {
            let options = '';
            for (const [value, label] of Object.entries(itemTypes)) {
                const selected = value === selectedValue ? 'selected' : '';
                options += `<option value="${value}" ${selected}>${label}</option>`;
            }
            return options;
        }

        // Generate unit type options HTML
        function getUnitTypeOptions(selectedValue = 'quantity') {
            let options = '';
            for (const [value, label] of Object.entries(unitTypes)) {
                const selected = value === selectedValue ? 'selected' : '';
                options += `<option value="${value}" ${selected}>${label}</option>`;
            }
            return options;
        }

        // Get all currently selected linked item values (excluding a specific row)
        function getSelectedLinkedValues(excludeRowId = null) {
            const selectedValues = new Set();
            const allLinkedSelects = document.querySelectorAll('.linked-item-select');

            allLinkedSelects.forEach(select => {
                // Get the row ID from the select's name attribute
                const match = select.name.match(/items\[(\d+)\]/);
                const rowId = match ? parseInt(match[1]) : null;

                // Skip the excluded row and empty values
                if (rowId !== excludeRowId && select.value) {
                    selectedValues.add(select.value);
                }
            });

            return selectedValues;
        }

        // Generate linked item options HTML (excluding already selected items)
        // For items with portions: show ONLY portion names (e.g., "Item - Large"), NOT the main item name
        // For items without portions: show the main item name
        function getLinkedItemOptions(currentValue = '', excludeValues = new Set()) {
            let options = '<option value="">-- Select Beverage/Dessert --</option>';

            finishedGoodsItems.forEach(item => {
                // Check if item has portions
                const hasPortions = item.portions && item.portions.length > 0;

                if (hasPortions) {
                    // Item has portions - show ONLY each portion, NOT the main item name
                    item.portions.forEach(portion => {
                        const value = `${item.id}_${portion.id}`;
                        // Skip if this value is selected in another row (but keep if it's the current row's value)
                        if (excludeValues.has(value) && value !== currentValue) {
                            return;
                        }
                        const selected = value === currentValue ? 'selected' : '';
                        options += `<option value="${value}" ${selected}
                                                    data-name="${portion.fullName}"
                                                    data-item-id="${item.id}"
                                                    data-portion-id="${portion.id}">
                                                    [${item.category}] ${portion.fullName}
                                                </option>`;
                    });
                    // DO NOT add the main item for items with portions
                } else {
                    // Item has NO portions - show main item name only
                    const value = `${item.id}`;
                    // Skip if this value is selected in another row (but keep if it's the current row's value)
                    if (excludeValues.has(value) && value !== currentValue) {
                        return;
                    }
                    const selected = value === currentValue ? 'selected' : '';
                    options += `<option value="${value}" ${selected}
                                                data-name="${item.name}"
                                                data-item-id="${item.id}">
                                                [${item.category}] ${item.name}
                                            </option>`;
                }
            });

            return options;
        }

        // Refresh all linked item dropdowns (to hide already-selected items)
        function refreshAllLinkedItemDropdowns() {
            const allLinkedSelects = document.querySelectorAll('.linked-item-select');

            allLinkedSelects.forEach(select => {
                // Get the row ID from the select's name attribute
                const match = select.name.match(/items\[(\d+)\]/);
                const rowId = match ? parseInt(match[1]) : null;

                // Get values selected in OTHER rows (exclude this row)
                const excludeValues = getSelectedLinkedValues(rowId);

                // Store current value
                const currentValue = select.value;

                // Regenerate options
                select.innerHTML = getLinkedItemOptions(currentValue, excludeValues);
            });
        }

        // Fetch new item code from server (with offset to avoid duplicates)
        async function fetchItemCode(type = 'raw_material', inputElement = null, retryCount = 0) {
            try {
                // Calculate offset dynamically by counting existing codes of this type in the form
                const baseOffset = getCodeOffset(type);
                const offset = baseOffset + retryCount;

                const response = await fetch(`{{ route('main-stock.generate-code') }}?type=${type}&offset=${offset}`);
                const data = await response.json();

                // Check if this code is already used in the form
                if (isCodeUsedInForm(data.code) && retryCount < 10) {
                    // Retry with a higher offset
                    return await fetchItemCode(type, inputElement, retryCount + 1);
                }

                if (inputElement) {
                    inputElement.value = data.code;
                }
                return data.code;
            } catch (error) {
                console.error('Error fetching item code:', error);
                return '';
            }
        }

        // Add a new row to the table
        async function addNewRow(focusOnName = true) {
            rowCounter++;
            const tbody = document.getElementById('itemsTableBody');

            // Fetch item code for the new row
            const itemCode = await fetchItemCode('raw_material');

            // Get already selected linked items to exclude from this row's dropdown
            const excludeValues = getSelectedLinkedValues();

            const row = document.createElement('tr');
            row.id = `row-${rowCounter}`;
            row.innerHTML = `
                                        <td>
                                            <span class="row-number">${rowCounter}</span>
                                        </td>
                                        <td>
                                            <input type="text" name="items[${rowCounter}][item_code]" class="item-code-input" value="${itemCode}" required>
                                            <input type="hidden" name="items[${rowCounter}][quantity]" value="0">
                                        </td>
                                        <td>
                                            <input type="text" name="items[${rowCounter}][item_name]" class="item-name-input" placeholder="e.g., Rice, Oil" required>
                                        </td>
                                        <td class="linked-item-cell" id="linked-cell-${rowCounter}">
                                            <span class="na-text">N/A</span>
                                            <select name="items[${rowCounter}][linked_item_id]" class="linked-item-select" onchange="onLinkedItemChange(this, ${rowCounter})">
                                                ${getLinkedItemOptions('', excludeValues)}
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[${rowCounter}][item_type]" class="item-type-select" onchange="onItemTypeChange(this, ${rowCounter})" required>
                                                ${getItemTypeOptions()}
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[${rowCounter}][unit_type]" class="unit-type-select" required>
                                                ${getUnitTypeOptions()}
                                            </select>
                                        </td>
                                        <td class="normalization-cell" id="normalization-cell-${rowCounter}">
                                            <input type="number" name="items[${rowCounter}][normalization]" class="normalization-input" step="0.0001" min="0" placeholder="Optional" title="Normalization factor for raw materials">
                                        </td>
                                        <td>
                                            <button type="button" class="remove-row-btn" onclick="removeRow(${rowCounter})" title="Remove row">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    `;

            tbody.appendChild(row);

            // Add Tab key listener to the normalization input to trigger new row
            const normalizationInput = row.querySelector('.normalization-input');
            normalizationInput.addEventListener('keydown', function (e) {
                if (e.key === 'Tab' && !e.shiftKey) {
                    // Check if this is the last row
                    const allRows = tbody.querySelectorAll('tr');
                    const lastRow = allRows[allRows.length - 1];
                    if (row === lastRow) {
                        e.preventDefault();
                        addNewRow(true);
                    }
                }
            });

            // Update row numbers and remove button states
            updateRowNumbers();

            // Focus on the item name input of the new row
            if (focusOnName) {
                setTimeout(() => {
                    const nameInput = row.querySelector('.item-name-input');
                    if (nameInput) {
                        nameInput.focus();
                    }
                }, 100);
            }
        }

        // Remove a row
        function removeRow(rowId) {
            const row = document.getElementById(`row-${rowId}`);
            if (row) {
                row.remove();
                updateRowNumbers();
                // Refresh dropdowns so removed row's selection becomes available again
                refreshAllLinkedItemDropdowns();
            }
        }

        // Update row numbers after add/remove
        function updateRowNumbers() {
            const tbody = document.getElementById('itemsTableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach((row, index) => {
                const numberSpan = row.querySelector('.row-number');
                if (numberSpan) {
                    numberSpan.textContent = index + 1;
                }
            });

            // Disable remove button if only one row
            const removeButtons = tbody.querySelectorAll('.remove-row-btn');
            removeButtons.forEach(btn => {
                btn.disabled = rows.length <= 1;
            });
        }

        // Handle item type change - regenerate code and show/hide linked item dropdown
        async function onItemTypeChange(selectElement, rowId) {
            const row = document.getElementById(`row-${rowId}`);
            if (!row) return;

            const codeInput = row.querySelector('.item-code-input');
            const linkedCell = document.getElementById(`linked-cell-${rowId}`);
            const linkedSelect = linkedCell.querySelector('.linked-item-select');
            const itemNameInput = row.querySelector('.item-name-input');
            const normalizationCell = document.getElementById(`normalization-cell-${rowId}`);
            const normalizationInput = normalizationCell ? normalizationCell.querySelector('.normalization-input') : null;
            const newType = selectElement.value;

            // Regenerate item code based on type
            await fetchItemCode(newType, codeInput);

            // Show/hide linked item dropdown for finished goods
            if (newType === 'finished_good') {
                linkedCell.classList.add('show');
                linkedSelect.required = true;
                // Clear item name when switching to finished good (will be filled by dropdown)
                itemNameInput.value = '';
                itemNameInput.readOnly = true;
                itemNameInput.placeholder = 'Auto-filled';
                itemNameInput.style.backgroundColor = '#f3f4f6';
                // Hide normalization for finished goods
                if (normalizationCell) {
                    normalizationCell.style.opacity = '0.5';
                    if (normalizationInput) {
                        normalizationInput.disabled = true;
                        normalizationInput.value = '';
                        normalizationInput.placeholder = 'N/A';
                    }
                }
            } else {
                linkedCell.classList.remove('show');
                linkedSelect.required = false;
                linkedSelect.value = '';
                itemNameInput.readOnly = false;
                itemNameInput.placeholder = 'e.g., Rice, Oil';
                itemNameInput.style.backgroundColor = '';
                // Show normalization only for raw materials
                if (normalizationCell) {
                    if (newType === 'raw_material') {
                        normalizationCell.style.opacity = '1';
                        if (normalizationInput) {
                            normalizationInput.disabled = false;
                            normalizationInput.placeholder = 'Optional';
                        }
                    } else {
                        normalizationCell.style.opacity = '0.5';
                        if (normalizationInput) {
                            normalizationInput.disabled = true;
                            normalizationInput.value = '';
                            normalizationInput.placeholder = 'N/A';
                        }
                    }
                }
            }
        }

        // Handle linked item selection - auto-fill item name and refresh dropdowns
        function onLinkedItemChange(selectElement, rowId) {
            const row = document.getElementById(`row-${rowId}`);
            if (!row) return;

            const itemNameInput = row.querySelector('.item-name-input');

            if (selectElement.value) {
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const itemName = selectedOption.getAttribute('data-name');
                if (itemName) {
                    itemNameInput.value = itemName;
                }
            } else {
                itemNameInput.value = '';
            }

            // Refresh all dropdowns to hide/show items based on selections
            refreshAllLinkedItemDropdowns();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            // Add first row
            addNewRow(false);
        });
    </script>
@endsection