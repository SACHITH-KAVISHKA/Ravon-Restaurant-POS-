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
                <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#667eea] to-[#764ba2]">Update Stock</h1>
                <p class="text-gray-500 text-sm">Add multiple items to stock</p>
            </div>
            <a href="{{ route('main-stock.index') }}" class="text-gray-600 hover:text-gray-800 font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Items
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Stock Update Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                    <!-- Form Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                        <h2 class="text-lg font-bold text-white">Add Stock Items</h2>
                        <p class="text-purple-200 text-sm">Select items and enter quantities to add to stock</p>
                    </div>

                    <form id="stockUpdateForm" class="p-6">
                        @csrf

                        <!-- Items Table -->
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200">
                                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Items</th>
                                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700 w-40">Qty</th>
                                        <th class="text-center py-4 px-4 text-sm font-semibold text-gray-700 w-20">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <!-- Initial Row -->
                                    <tr class="item-row border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-4 px-4">
                                            <select name="items[0][item_id]" class="item-select form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-200 bg-white">
                                                <option value="">Select Item</option>
                                                @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-unit="{{ $item->unit_abbreviation }}" data-name="{{ $item->item_name }}">
                                                    {{ $item->item_code }} - {{ $item->item_name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="py-4 px-4">
                                            <input type="number" name="items[0][quantity]" class="qty-input form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-center" step="0.001" min="0.001" placeholder="0.000">
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <button type="button" class="delete-row-btn p-3 text-red-500 hover:text-white hover:bg-red-500 rounded-lg transition border border-red-200 hover:border-red-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
                            <button type="button" id="clearFormBtn" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                                Clear Form
                            </button>
                            <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold rounded-lg transition">
                                Update Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stock View -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden sticky top-6">
                    <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                        <h3 class="font-semibold text-gray-800">Low Stock Items</h3>
                        <p class="text-xs text-gray-500">Items below minimum level</p>
                    </div>
                    <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                        @php
                        $lowStockItems = $items->filter(fn($item) => $item->isLowStock());
                        @endphp
                        @forelse($lowStockItems as $item)
                        <div class="p-3 hover:bg-gray-50 cursor-pointer item-quick-select" data-item-id="{{ $item->id }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-sm text-gray-800">{{ $item->item_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->item_code }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-red-600">{{ number_format($item->quantity, 2) }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->unit_abbreviation }}</p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    @php
                                    $percentage = $item->min_quantity > 0 ? min(100, ($item->quantity / $item->min_quantity) * 100) : 0;
                                    @endphp
                                    <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Min: {{ number_format($item->min_quantity, 2) }} {{ $item->unit_abbreviation }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="p-6 text-center">
                            <svg class="w-12 h-12 mx-auto text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">All items have sufficient stock</p>
                        </div>
                        @endforelse
                    </div>
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
    $itemsArray = $items->map(function($item) {
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

        // Generate option HTML for select
        function getOptionsHtml(excludeIds = []) {
            let html = '<option value="">Select Item</option>';
            itemsData.forEach(item => {
                if (!excludeIds.includes(item.id)) {
                    html += `<option value="${item.id}" data-unit="${item.unit}" data-name="${item.name}">${item.code} - ${item.name}</option>`;
                }
            });
            return html;
        }

        // Get selected item IDs (excluding the given row)
        function getSelectedItemIds(excludeRow = null) {
            const ids = [];
            document.querySelectorAll('.item-select').forEach(select => {
                if (select.closest('.item-row') !== excludeRow && select.value) {
                    ids.push(parseInt(select.value));
                }
            });
            return ids;
        }

        // Add new row
        function addNewRow() {
            const selectedIds = getSelectedItemIds();
            const newRow = document.createElement('tr');
            newRow.className = 'item-row border-b border-gray-100 hover:bg-gray-50';
            newRow.innerHTML = `
                <td class="py-4 px-4">
                    <select name="items[${rowIndex}][item_id]" class="item-select form-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-200 bg-white">
                        ${getOptionsHtml(selectedIds)}
                    </select>
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

            // Focus on the new select
            const newSelect = newRow.querySelector('.item-select');
            newSelect.focus();

            // Add event listeners
            attachRowEventListeners(newRow);
        }

        // Attach event listeners to row elements
        function attachRowEventListeners(row) {
            const qtyInput = row.querySelector('.qty-input');
            const deleteBtn = row.querySelector('.delete-row-btn');
            const itemSelect = row.querySelector('.item-select');

            // TAB on quantity input adds new row
            qtyInput.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' && !e.shiftKey) {
                    const itemValue = itemSelect.value;
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
                    updateDropdowns();
                } else {
                    // Clear the row instead of deleting if it's the last one
                    itemSelect.value = '';
                    qtyInput.value = '';
                }
            });

            // Update dropdowns when item is selected
            itemSelect.addEventListener('change', function() {
                updateDropdowns();
            });
        }

        // Update all dropdowns to exclude selected items
        function updateDropdowns() {
            document.querySelectorAll('.item-row').forEach(row => {
                const select = row.querySelector('.item-select');
                const currentValue = select.value;
                const selectedIds = getSelectedItemIds(row);

                // Rebuild options
                select.innerHTML = getOptionsHtml(selectedIds);

                // Re-select current value if it's still valid
                if (currentValue) {
                    select.value = currentValue;
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
                    row.querySelector('.item-select').value = '';
                    row.querySelector('.qty-input').value = '';
                }
            });
            document.getElementById('reference_number').value = '';
            document.getElementById('notes').value = '';
            rowIndex = 1;
            updateDropdowns();
        });

        // Quick select from low stock items
        document.querySelectorAll('.item-quick-select').forEach(el => {
            el.addEventListener('click', function() {
                const itemId = this.dataset.itemId;

                // Find first empty row or add new row
                let targetSelect = null;
                document.querySelectorAll('.item-select').forEach(select => {
                    if (!select.value && !targetSelect) {
                        targetSelect = select;
                    }
                });

                if (!targetSelect) {
                    addNewRow();
                    targetSelect = tableBody.lastElementChild.querySelector('.item-select');
                }

                targetSelect.value = itemId;
                updateDropdowns();
                targetSelect.closest('.item-row').querySelector('.qty-input').focus();
            });
        });

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Collect items data
            const items = [];
            document.querySelectorAll('.item-row').forEach(row => {
                const itemId = row.querySelector('.item-select').value;
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
