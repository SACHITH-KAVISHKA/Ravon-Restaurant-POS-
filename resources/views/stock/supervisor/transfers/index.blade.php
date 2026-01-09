@extends('layouts.app')

@section('title', 'Stock Transfers - Supervisor')

@push('styles')
<style>
    .tab-btn {
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }

    .tab-btn.active {
        border-bottom-color: #667eea;
        color: #667eea;
    }

    .tab-btn:not(.active):hover {
        background-color: #f9fafb;
    }

    .item-row {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .empty-state-icon {
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .modal-backdrop {
        backdrop-filter: blur(4px);
    }

    .quantity-input::-webkit-inner-spin-button,
    .quantity-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .quantity-input {
        -moz-appearance: textfield;
        appearance: textfield;
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
                <h1 class="text-2xl font-bold text-gray-800">Stock Transfers</h1>
                <p class="text-gray-500 text-sm">Transfer raw materials to cashier</p>
            </div>
            <button onclick="openCreateTransferModal()" class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-lg font-semibold hover:from-emerald-700 hover:to-emerald-800 transition shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Transfer
            </button>
        </div>

        <!-- Tabs Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-100 px-6">
                <div class="flex gap-6">
                    <button onclick="switchTab('pending')" id="tab-pending" class="tab-btn active py-4 font-medium text-sm flex items-center gap-2">
                        <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                        Pending
                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">{{ $counts['pending'] }}</span>
                    </button>
                    <button onclick="switchTab('accepted')" id="tab-accepted" class="tab-btn py-4 font-medium text-sm flex items-center gap-2 text-gray-500">
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        Accepted
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">{{ $counts['accepted'] }}</span>
                    </button>
                    <button onclick="switchTab('rejected')" id="tab-rejected" class="tab-btn py-4 font-medium text-sm flex items-center gap-2 text-gray-500">
                        <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                        Rejected
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">{{ $counts['rejected'] }}</span>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Pending Tab -->
                <div id="content-pending" class="tab-content">
                    <div id="pending-container">
                        <!-- Will load via AJAX -->
                    </div>
                </div>

                <!-- Accepted Tab -->
                <div id="content-accepted" class="tab-content hidden">
                    <div id="accepted-container">
                        <!-- Will load via AJAX -->
                    </div>
                </div>

                <!-- Rejected Tab -->
                <div id="content-rejected" class="tab-content hidden">
                    <div id="rejected-container">
                        <!-- Will load via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Transfer Modal -->
<div id="createTransferModal" class="fixed inset-0 bg-black/50 modal-backdrop z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-600 to-emerald-700">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Create Stock Transfer</h2>
                <button onclick="closeCreateTransferModal()" class="text-white/80 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
            <!-- Add All Items Button -->
            <div class="flex justify-end mb-4">
                <button onclick="addAllItems()" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition shadow-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add All Items
                </button>
            </div>

            <!-- Transfer Items Table -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="transfer-items-table">
                    <thead>
                        <tr class="bg-emerald-500 text-white">
                            <th class="px-4 py-3 text-left font-semibold">Item</th>
                            <th class="px-4 py-3 text-center font-semibold w-32">Available</th>
                            <th class="px-4 py-3 text-center font-semibold w-32">Unit</th>
                            <th class="px-4 py-3 text-center font-semibold w-40">Transfer Qty</th>
                            <th class="px-4 py-3 text-center font-semibold w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody id="transfer-items-body">
                        <!-- First row will be added automatically -->
                    </tbody>
                </table>
            </div>

            <!-- Notes -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea id="transfer-notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none" placeholder="Add any notes for this transfer..."></textarea>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
            <button onclick="closeCreateTransferModal()" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-100 transition">
                Cancel
            </button>
            <button onclick="submitTransfer()" id="submit-transfer-btn" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-lg font-medium hover:from-emerald-700 hover:to-emerald-800 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                Submit Transfer
            </button>
        </div>
    </div>
</div>

<!-- View Transfer Modal -->
<div id="viewTransferModal" class="fixed inset-0 bg-black/50 modal-backdrop z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-600 to-emerald-700">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-white" id="view-transfer-number">Transfer Details</h2>
                    <p class="text-emerald-200 text-sm" id="view-transfer-date"></p>
                </div>
                <button onclick="closeViewTransferModal()" class="text-white/80 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6" id="view-transfer-body">
            <!-- Content loaded via JS -->
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <button onclick="closeViewTransferModal()" class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                Close
            </button>
        </div>
    </div>
</div>

@endsection

<script type="application/json" id="main-stock-data">
    @json($mainStockItems)
</script>

@push('scripts')
<script>
    // Main stock items data from PHP
    const mainStockItems = JSON.parse(document.getElementById('main-stock-data').textContent);

    let rowCounter = 0;
    let currentViewingTransfer = null;

    // Tab switching
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('text-gray-500');
        });
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));

        const activeTab = document.getElementById('tab-' + tab);
        activeTab.classList.add('active');
        activeTab.classList.remove('text-gray-500');
        document.getElementById('content-' + tab).classList.remove('hidden');

        // Load content for tabs that need AJAX
        if (tab === 'pending' || tab === 'accepted' || tab === 'rejected') {
            loadTransfersByStatus(tab);
        }
    }

    // Load transfers by status
    async function loadTransfersByStatus(status) {
        const container = document.getElementById(`${status}-container`);
        container.innerHTML = '<div class="text-center py-8"><div class="animate-spin w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full mx-auto"></div></div>';

        try {
            const response = await fetch(`{{ route('stock-transfer.history') }}?status=${status}`);
            const data = await response.json();

            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-16">
                            <svg class="w-24 h-24 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-gray-500">No ${status.charAt(0).toUpperCase() + status.slice(1)} Transfers</h3>
                        </div>
                    `;
                } else {
                    renderTransfersTable(container, data.data);
                }
            }
        } catch (error) {
            container.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load transfers</div>';
        }
    }

    // Render transfers table
    function renderTransfersTable(container, transfers) {
        let rows = transfers.map(t => `
            <tr class="hover:bg-purple-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-emerald-600 font-mono font-semibold">${t.transfer_number}</span>
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">${t.items.length} items</span>
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        ${t.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ''}
                        ${t.status === 'accepted' ? 'bg-green-100 text-green-700' : ''}
                        ${t.status === 'rejected' ? 'bg-red-100 text-red-700' : ''}
                    ">${t.status.charAt(0).toUpperCase() + t.status.slice(1)}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm">
                    ${new Date(t.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                    <button onclick="viewTransfer(${t.id})" class="p-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white rounded-lg transition" title="View Details">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </td>
            </tr>
        `).join('');

        container.innerHTML = `
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Transfer #</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Items</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${rows}
                    </tbody>
                </table>
            </div>
        `;
    }

    // Create Transfer Modal
    function openCreateTransferModal() {
        rowCounter = 0;
        const tbody = document.getElementById('transfer-items-body');
        tbody.innerHTML = '';
        document.getElementById('transfer-notes').value = '';
        document.getElementById('submit-transfer-btn').disabled = true;

        // Add first row automatically
        addItemRow();

        document.getElementById('createTransferModal').classList.remove('hidden');
    }

    function closeCreateTransferModal() {
        document.getElementById('createTransferModal').classList.add('hidden');
    }

    // Add all items with their full quantities
    function addAllItems() {
        const tbody = document.getElementById('transfer-items-body');
        tbody.innerHTML = ''; // Clear existing rows
        rowCounter = 0;

        // Filter items that have quantity > 0
        const itemsWithStock = mainStockItems.filter(item => parseFloat(item.quantity) > 0);

        if (itemsWithStock.length === 0) {
            showToast('No items with available stock to transfer', 'error');
            addItemRow(); // Add empty row
            return;
        }

        // Add a row for each item with stock
        itemsWithStock.forEach(item => {
            const rowId = rowCounter++;
            const row = document.createElement('tr');
            row.id = `item-row-${rowId}`;
            row.className = 'border-b border-gray-200 item-row';
            row.innerHTML = `
                <td class="px-4 py-3">
                    <select id="item-select-${rowId}" onchange="onItemSelect(${rowId})" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white">
                        <option value="">Select Item</option>
                        ${mainStockItems.map(i => `
                            <option value="${i.id}" 
                                    data-qty="${i.quantity}" 
                                    data-unit="${i.unit_abbreviation}"
                                    data-name="${i.item_name}"
                                    ${i.id === item.id ? 'selected' : ''}>${i.item_code} - ${i.item_name}</option>
                        `).join('')}
                    </select>
                </td>
                <td class="px-4 py-3 text-center">
                    <input type="text" id="available-qty-${rowId}" value="${parseFloat(item.quantity).toFixed(3)}" readonly
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-center text-gray-600">
                </td>
                <td class="px-4 py-3 text-center">
                    <input type="text" id="unit-${rowId}" value="${item.unit_abbreviation}" readonly
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-center text-gray-600">
                </td>
                <td class="px-4 py-3 text-center">
                    <input type="number" id="transfer-qty-${rowId}" value="${parseFloat(item.quantity).toFixed(3)}" min="0.001" step="0.001"
                        onchange="validateQuantity(${rowId})"
                        onkeydown="handleTransferQtyKeydown(event, ${rowId})"
                        class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-center"
                        placeholder="0">
                </td>
                <td class="px-4 py-3 text-center">
                    <button onclick="removeItemRow(${rowId})" class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Update dropdowns to exclude already selected items
        updateDropdowns();
        // Enable submit button
        updateSubmitButton();

        showToast(`Added ${itemsWithStock.length} items with full quantities`, 'success');
    }

    // Add a new item row to the table
    function addItemRow() {
        const tbody = document.getElementById('transfer-items-body');
        const rowId = rowCounter++;

        const row = document.createElement('tr');
        row.id = `item-row-${rowId}`;
        row.className = 'border-b border-gray-200 item-row';
        row.innerHTML = `
            <td class="px-4 py-3">
                <select id="item-select-${rowId}" onchange="onItemSelect(${rowId})" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white">
                    <option value="">Select Item</option>
                    ${mainStockItems.map(item => `
                        <option value="${item.id}" 
                                data-qty="${item.quantity}" 
                                data-unit="${item.unit_abbreviation}"
                                data-name="${item.item_name}">${item.item_code} - ${item.item_name}</option>
                    `).join('')}
                </select>
            </td>
            <td class="px-4 py-3 text-center">
                <input type="text" id="available-qty-${rowId}" value="0" readonly
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-center text-gray-600">
            </td>
            <td class="px-4 py-3 text-center">
                <input type="text" id="unit-${rowId}" value="-" readonly
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-center text-gray-600">
            </td>
            <td class="px-4 py-3 text-center">
                <input type="number" id="transfer-qty-${rowId}" value="" min="0.001" step="0.001"
                    onchange="validateQuantity(${rowId})"
                    onkeydown="handleTransferQtyKeydown(event, ${rowId})"
                    class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-center"
                    placeholder="0">
            </td>
            <td class="px-4 py-3 text-center">
                <button onclick="removeItemRow(${rowId})" class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        updateSubmitButton();
    }

    // Handle item selection
    function onItemSelect(rowId) {
        const select = document.getElementById(`item-select-${rowId}`);
        const selectedOption = select.options[select.selectedIndex];
        const availableQty = selectedOption.dataset.qty || 0;
        const unit = selectedOption.dataset.unit || '-';

        document.getElementById(`available-qty-${rowId}`).value = parseFloat(availableQty).toFixed(3);
        document.getElementById(`unit-${rowId}`).value = unit;

        updateDropdowns();
        updateSubmitButton();
    }

    // Update dropdowns to prevent duplicate selections
    function updateDropdowns() {
        const rows = document.querySelectorAll('.item-row');
        const selectedIds = [];

        rows.forEach(row => {
            const select = row.querySelector('select');
            if (select && select.value) {
                selectedIds.push(select.value);
            }
        });

        rows.forEach(row => {
            const select = row.querySelector('select');
            if (select) {
                const currentValue = select.value;
                Array.from(select.options).forEach(option => {
                    if (option.value && option.value !== currentValue) {
                        option.disabled = selectedIds.includes(option.value);
                    }
                });
            }
        });
    }

    // Validate quantity
    function validateQuantity(rowId) {
        const qtyInput = document.getElementById(`transfer-qty-${rowId}`);
        const availableInput = document.getElementById(`available-qty-${rowId}`);
        let qty = parseFloat(qtyInput.value) || 0;
        const available = parseFloat(availableInput.value) || 0;

        if (qty < 0) qty = 0;
        if (qty > available) {
            qty = available;
            showToast('Quantity cannot exceed available stock', 'warning');
        }

        qtyInput.value = qty > 0 ? qty : '';
        updateSubmitButton();
    }

    // Handle TAB key on transfer quantity field
    function handleTransferQtyKeydown(event, rowId) {
        if (event.key === 'Tab' && !event.shiftKey) {
            const currentRow = document.getElementById(`item-row-${rowId}`);
            const allRows = document.querySelectorAll('.item-row');
            const lastRow = allRows[allRows.length - 1];

            if (currentRow === lastRow) {
                const qty = parseFloat(document.getElementById(`transfer-qty-${rowId}`).value) || 0;
                if (qty > 0) {
                    event.preventDefault();
                    addItemRow();

                    setTimeout(() => {
                        const newRowId = rowCounter - 1;
                        const newSelect = document.getElementById(`item-select-${newRowId}`);
                        if (newSelect) {
                            newSelect.focus();
                        }
                    }, 50);
                }
            }
        }
    }

    // Remove item row
    function removeItemRow(rowId) {
        const row = document.getElementById(`item-row-${rowId}`);
        if (row) {
            row.remove();
            updateDropdowns();
            updateSubmitButton();
        }
    }

    // Update submit button state
    function updateSubmitButton() {
        const rows = document.querySelectorAll('.item-row');
        let hasValidItem = false;

        rows.forEach(row => {
            const select = row.querySelector('select');
            const qtyInput = row.querySelector('input[type="number"]');
            if (select && select.value && qtyInput && parseFloat(qtyInput.value) > 0) {
                hasValidItem = true;
            }
        });

        document.getElementById('submit-transfer-btn').disabled = !hasValidItem;
    }

    // Get selected items
    function getSelectedItems() {
        const rows = document.querySelectorAll('.item-row');
        const items = [];

        rows.forEach(row => {
            const select = row.querySelector('select');
            const qtyInput = row.querySelector('input[type="number"]');
            if (select && select.value && qtyInput && parseFloat(qtyInput.value) > 0) {
                items.push({
                    item_id: parseInt(select.value),
                    quantity: parseFloat(qtyInput.value)
                });
            }
        });

        return items;
    }

    // Submit transfer
    async function submitTransfer() {
        const selectedItems = getSelectedItems();

        if (selectedItems.length === 0) {
            showToast('Please select at least one item with quantity', 'error');
            return;
        }

        const submitBtn = document.getElementById('submit-transfer-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Submitting...';

        try {
            const response = await fetch('{{ route("stock-transfer.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify({
                    items: selectedItems,
                    notes: document.getElementById('transfer-notes').value
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Transfer created successfully! ' + data.transfer_number, 'success');
                closeCreateTransferModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to create transfer', 'error');
            }
        } catch (error) {
            showToast('An error occurred. Please try again.', 'error');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Transfer';
    }

    // View Transfer Details
    async function viewTransfer(transferId) {
        try {
            const response = await fetch(`/stock-transfer/${transferId}`);
            const data = await response.json();

            if (data.success) {
                currentViewingTransfer = data.data;
                renderTransferDetails(data.data);
                document.getElementById('viewTransferModal').classList.remove('hidden');
            }
        } catch (error) {
            showToast('Failed to load transfer details', 'error');
        }
    }

    function renderTransferDetails(transfer) {
        document.getElementById('view-transfer-number').textContent = transfer.transfer_number;
        document.getElementById('view-transfer-date').textContent = new Date(transfer.created_at).toLocaleString();

        let statusBadge = '';
        switch (transfer.status) {
            case 'pending':
                statusBadge = '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">Pending</span>';
                break;
            case 'accepted':
                statusBadge = '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Accepted</span>';
                break;
            case 'rejected':
                statusBadge = '<span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Rejected</span>';
                break;
        }

        let itemsHtml = transfer.items.map(item => `
            <tr class="border-b border-gray-100">
                <td class="py-3 font-medium text-gray-700">${item.item_name}</td>
                <td class="py-3 text-center text-gray-600">${parseFloat(item.quantity).toFixed(3)}</td>
                <td class="py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        ${item.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ''}
                        ${item.status === 'accepted' ? 'bg-green-100 text-green-700' : ''}
                        ${item.status === 'rejected' ? 'bg-red-100 text-red-700' : ''}
                    ">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span>
                </td>
            </tr>
        `).join('');

        document.getElementById('view-transfer-body').innerHTML = `
            <div class="mb-4 flex items-center gap-3">
                <span class="text-gray-500">Status:</span>
                ${statusBadge}
            </div>
            
            ${transfer.cashier ? `<div class="mb-4 p-3 bg-blue-50 rounded-lg"><p class="text-sm text-blue-700"><strong>Responded by:</strong> ${transfer.cashier.name}</p></div>` : ''}
            
            ${transfer.notes ? `<div class="mb-4 p-3 bg-gray-50 rounded-lg"><p class="text-sm text-gray-600"><strong>Notes:</strong> ${transfer.notes}</p></div>` : ''}
            
            ${transfer.cashier_notes ? `<div class="mb-4 p-3 bg-purple-50 rounded-lg"><p class="text-sm text-purple-700"><strong>Cashier Notes:</strong> ${transfer.cashier_notes}</p></div>` : ''}
            
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm text-gray-500 border-b border-gray-200">
                        <th class="pb-2">Item</th>
                        <th class="pb-2 text-center">Quantity</th>
                        <th class="pb-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
        `;
    }

    function closeViewTransferModal() {
        document.getElementById('viewTransferModal').classList.add('hidden');
        currentViewingTransfer = null;
    }

    // Close modals on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateTransferModal();
            closeViewTransferModal();
        }
    });

    // Event delegation for view transfer buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.view-transfer-btn');
        if (btn) {
            const transferId = btn.dataset.transferId;
            if (transferId) {
                viewTransfer(parseInt(transferId));
            }
        }
    });

    // Load pending transfers on page load (default tab)
    document.addEventListener('DOMContentLoaded', function() {
        loadTransfersByStatus('pending');
    });
</script>
@endpush