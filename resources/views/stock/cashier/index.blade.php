@extends('layouts.app')

@section('title', 'Stock Requests - Cashier')

@push('styles')
<style>
    .tab-btn {
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .tab-btn:not(.active):hover {
        background-color: #f3f4f6;
    }

    .request-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .request-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .request-card.pending {
        border-left-color: #fbbf24;
    }

    .request-card.accepted {
        border-left-color: #10b981;
    }

    .request-card.rejected {
        border-left-color: #ef4444;
    }

    .request-card.partially_accepted {
        border-left-color: #3b82f6;
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

    .modal-backdrop {
        backdrop-filter: blur(4px);
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
                <h1 class="text-2xl font-bold text-gray-800">Stock Requests</h1>
                <p class="text-gray-500 text-sm">Request stock items from supervisor</p>
            </div>
            <button onclick="openCreateRequestModal()" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-purple-800 transition shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Request
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-100 px-4 pt-4">
                <div class="flex gap-2">
                    <button onclick="switchTab('my-requests')" id="tab-my-requests" class="tab-btn active px-4 py-2 rounded-t-lg font-medium text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        My Requests
                    </button>
                    <button onclick="switchTab('new-responses')" id="tab-new-responses" class="tab-btn px-4 py-2 rounded-t-lg font-medium text-sm flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        New Responses
                        <span id="new-responses-badge" class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full hidden">0</span>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- My Requests Tab -->
                <div id="content-my-requests" class="tab-content">
                    @if($myRequests->isEmpty())
                    <div class="text-center py-16">
                        <svg class="w-20 h-20 mx-auto text-gray-300 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-600">No Requests Yet</h3>
                        <p class="text-gray-400 mt-1">Create your first stock request to get started</p>
                    </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Request #</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($myRequests as $request)
                                <tr class="hover:bg-purple-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-purple-600 font-mono font-semibold">{{ $request->request_number }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">{{ $request->items->count() }} items</span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-sm font-medium
                                            @if($request->status === 'pending') bg-yellow-100 text-yellow-700
                                            @elseif($request->status === 'accepted') bg-green-100 text-green-700
                                            @elseif($request->status === 'rejected') bg-red-100 text-red-700
                                            @else bg-blue-100 text-blue-700
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm">
                                        {{ $request->created_at->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <button onclick="viewRequestDetails({{ $request->id }})" class="p-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white rounded-lg transition" title="View Details">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">
                        {{ $myRequests->links() }}
                    </div>
                    @endif
                </div>

                <!-- New Responses Tab -->
                <div id="content-new-responses" class="tab-content hidden">
                    <div id="responses-container">
                        <!-- Will be loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div id="createRequestModal" class="fixed inset-0 bg-black/50 modal-backdrop z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-600 to-purple-700">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Create Stock Request</h2>
                <button onclick="closeCreateRequestModal()" class="text-white/80 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
            <!-- Request Items Table -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="request-items-table">
                    <thead>
                        <tr class="bg-indigo-500 text-white">
                            <th class="px-4 py-3 text-left font-semibold">Item</th>
                            <th class="px-4 py-3 text-center font-semibold w-40">Available Qty</th>
                            <th class="px-4 py-3 text-center font-semibold w-40">Transfer Qty</th>
                            <th class="px-4 py-3 text-center font-semibold w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody id="request-items-body">
                        <!-- First row will be added automatically -->
                    </tbody>
                </table>
            </div>

            <!-- Notes -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea id="request-notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none" placeholder="Add any special notes..."></textarea>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
            <button onclick="closeCreateRequestModal()" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-100 transition">
                Cancel
            </button>
            <button onclick="submitRequest()" id="submit-request-btn" class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg font-medium hover:from-purple-700 hover:to-purple-800 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                Submit Request
            </button>
        </div>
    </div>
</div>

<!-- View Request Details Modal -->
<div id="viewRequestModal" class="fixed inset-0 bg-black/50 modal-backdrop z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100" id="view-modal-header">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800" id="view-request-number">Request Details</h2>
                    <p class="text-sm text-gray-500" id="view-request-date"></p>
                </div>
                <button onclick="closeViewRequestModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6" id="view-request-body">
            <!-- Content loaded via JS -->
        </div>

        <!-- Modal Footer (for responding) -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50" id="view-modal-footer" style="display: none;">
            <div class="flex gap-3">
                <button onclick="respondToRequest('reject')" class="flex-1 px-6 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition">
                    Reject
                </button>
                <button onclick="respondToRequest('accept')" class="flex-1 px-6 py-3 bg-green-500 text-white rounded-lg font-medium hover:bg-green-600 transition">
                    Accept
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Stock items data from PHP - includes items with portions/sizes
    const availableItems = @json($stockItems);

    let rowCounter = 0;
    let currentViewingRequest = null;

    // Tab switching
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));

        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('content-' + tab).classList.remove('hidden');

        if (tab === 'new-responses') {
            loadNewResponses();
        }
    }

    // Create Request Modal
    function openCreateRequestModal() {
        rowCounter = 0;
        const tbody = document.getElementById('request-items-body');
        tbody.innerHTML = '';
        document.getElementById('request-notes').value = '';
        document.getElementById('submit-request-btn').disabled = true;

        // Add first row automatically
        addItemRow();

        document.getElementById('createRequestModal').classList.remove('hidden');
    }

    function closeCreateRequestModal() {
        document.getElementById('createRequestModal').classList.add('hidden');
    }

    // Add a new item row to the table
    function addItemRow() {
        const tbody = document.getElementById('request-items-body');
        const rowId = rowCounter++;

        const row = document.createElement('tr');
        row.id = `item-row-${rowId}`;
        row.className = 'border-b border-gray-200 item-row';
        row.innerHTML = `
            <td class="px-4 py-3">
                <select id="item-select-${rowId}" onchange="onItemSelect(${rowId})" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white">
                    <option value="">Select Item</option>
                    ${availableItems.map(item => `
                        <option value="${item.id}" 
                                data-qty="${item.available_qty}" 
                                data-item-id="${item.item_id}"
                                data-modifier-id="${item.modifier_id || ''}"
                                data-unit="${item.unit}">${item.name} (${item.category})</option>
                    `).join('')}
                </select>
            </td>
            <td class="px-4 py-3 text-center">
                <input type="text" id="available-qty-${rowId}" value="0" readonly
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-center text-gray-600">
            </td>
            <td class="px-4 py-3 text-center">
                <input type="number" id="transfer-qty-${rowId}" value="0" min="0" step="0.01"
                    onchange="validateQuantity(${rowId})"
                    onkeydown="handleTransferQtyKeydown(event, ${rowId})"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-center">
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

    // Handle item selection from dropdown
    function onItemSelect(rowId) {
        const select = document.getElementById(`item-select-${rowId}`);
        const selectedOption = select.options[select.selectedIndex];
        const availableQty = selectedOption.dataset.qty || 0;

        // Update available qty field
        document.getElementById(`available-qty-${rowId}`).value = availableQty;

        // Update selected items in other dropdowns to prevent duplicates
        updateDropdowns();
        updateSubmitButton();
    }

    // Update dropdowns to show which items are already selected
    function updateDropdowns() {
        const rows = document.querySelectorAll('.item-row');
        const selectedIds = [];

        // Collect all selected item IDs
        rows.forEach(row => {
            const select = row.querySelector('select');
            if (select && select.value) {
                selectedIds.push(select.value);
            }
        });

        // Disable already selected items in other dropdowns
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
        const qty = parseFloat(document.getElementById(`transfer-qty-${rowId}`).value) || 0;
        if (qty < 0) {
            document.getElementById(`transfer-qty-${rowId}`).value = 0;
        }
        updateSubmitButton();
    }

    // Handle TAB key on transfer quantity field - auto add new row
    function handleTransferQtyKeydown(event, rowId) {
        if (event.key === 'Tab' && !event.shiftKey) {
            const currentRow = document.getElementById(`item-row-${rowId}`);
            const allRows = document.querySelectorAll('.item-row');
            const lastRow = allRows[allRows.length - 1];

            // Only add new row if this is the last row and has a valid quantity
            if (currentRow === lastRow) {
                const qty = parseFloat(document.getElementById(`transfer-qty-${rowId}`).value) || 0;
                if (qty > 0) {
                    event.preventDefault(); // Prevent default tab behavior
                    addItemRow();

                    // Focus on the new row's item select after a short delay
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

    // Remove an item row
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

        document.getElementById('submit-request-btn').disabled = !hasValidItem;
    }

    // Collect selected items for submission
    function getSelectedItems() {
        const rows = document.querySelectorAll('.item-row');
        const items = [];

        rows.forEach(row => {
            const select = row.querySelector('select');
            const qtyInput = row.querySelector('input[type="number"]');
            if (select && select.value && qtyInput && parseFloat(qtyInput.value) > 0) {
                const selectedOption = select.options[select.selectedIndex];
                items.push({
                    stock_id: parseInt(select.value),
                    item_id: parseInt(selectedOption.dataset.itemId),
                    modifier_id: selectedOption.dataset.modifierId ? parseInt(selectedOption.dataset.modifierId) : null,
                    name: selectedOption.text,
                    quantity: parseFloat(qtyInput.value)
                });
            }
        });

        return items;
    }

    // Submit request
    async function submitRequest() {
        const selectedItems = getSelectedItems();

        if (selectedItems.length === 0) {
            showToast('Please select at least one item with quantity', 'error');
            return;
        }

        const submitBtn = document.getElementById('submit-request-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Submitting...';

        try {
            const response = await fetch('{{ route("stock.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify({
                    items: selectedItems.map(item => ({
                        item_id: item.item_id,
                        modifier_id: item.modifier_id,
                        quantity: item.quantity
                    })),
                    notes: document.getElementById('request-notes').value
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Request created successfully! ' + data.request_number, 'success');
                closeCreateRequestModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to create request', 'error');
            }
        } catch (error) {
            showToast('An error occurred. Please try again.', 'error');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Request';
    }

    // View Request Details
    async function viewRequestDetails(requestId) {
        try {
            const response = await fetch(`/stock/${requestId}`);
            const data = await response.json();

            if (data.success) {
                currentViewingRequest = data.data;
                renderRequestDetails(data.data);
                document.getElementById('viewRequestModal').classList.remove('hidden');
            }
        } catch (error) {
            showToast('Failed to load request details', 'error');
        }
    }

    function renderRequestDetails(request) {
        document.getElementById('view-request-number').textContent = request.request_number;
        document.getElementById('view-request-date').textContent = new Date(request.created_at).toLocaleString();

        let statusBadge = '';
        switch (request.status) {
            case 'pending':
                statusBadge = '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">Pending</span>';
                break;
            case 'accepted':
                statusBadge = '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Accepted</span>';
                break;
            case 'rejected':
                statusBadge = '<span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Rejected</span>';
                break;
            case 'partially_accepted':
                statusBadge = '<span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">Partially Accepted</span>';
                break;
        }

        let itemsHtml = request.items.map(item => `
            <tr class="border-b border-gray-100">
                <td class="py-3 font-medium text-gray-700">${item.item_name}</td>
                <td class="py-3 text-center text-gray-600">${item.requested_quantity}</td>
                <td class="py-3 text-center ${item.approved_quantity !== null ? 'text-green-600 font-medium' : 'text-gray-400'}">${item.approved_quantity !== null ? item.approved_quantity : '-'}</td>
                <td class="py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        ${item.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ''}
                        ${item.status === 'approved' ? 'bg-green-100 text-green-700' : ''}
                        ${item.status === 'rejected' ? 'bg-red-100 text-red-700' : ''}
                        ${item.status === 'partially_approved' ? 'bg-blue-100 text-blue-700' : ''}
                    ">${item.status.replace('_', ' ')}</span>
                </td>
            </tr>
        `).join('');

        let body = `
            <div class="mb-4 flex items-center gap-3">
                <span class="text-gray-500">Status:</span>
                ${statusBadge}
            </div>
            
            ${request.notes ? `<div class="mb-4 p-3 bg-gray-50 rounded-lg"><p class="text-sm text-gray-600"><strong>Your Notes:</strong> ${request.notes}</p></div>` : ''}
            
            ${request.supervisor_notes ? `<div class="mb-4 p-3 bg-purple-50 rounded-lg"><p class="text-sm text-purple-700"><strong>Supervisor Notes:</strong> ${request.supervisor_notes}</p></div>` : ''}
            
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm text-gray-500 border-b border-gray-200">
                        <th class="pb-2">Item</th>
                        <th class="pb-2 text-center">Requested</th>
                        <th class="pb-2 text-center">Approved</th>
                        <th class="pb-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
        `;

        document.getElementById('view-request-body').innerHTML = body;

        // Show/hide response buttons
        const footer = document.getElementById('view-modal-footer');
        if (request.status !== 'pending' && request.cashier_response === 'pending') {
            footer.style.display = 'block';
        } else {
            footer.style.display = 'none';
        }
    }

    function closeViewRequestModal() {
        document.getElementById('viewRequestModal').classList.add('hidden');
        currentViewingRequest = null;
    }

    // Respond to supervisor's decision
    async function respondToRequest(action) {
        if (!currentViewingRequest) return;

        try {
            const response = await fetch(`/stock/${currentViewingRequest.id}/cashier-respond`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify({
                    action
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(`Response recorded: ${action}ed`, 'success');
                closeViewRequestModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to respond', 'error');
            }
        } catch (error) {
            showToast('An error occurred. Please try again.', 'error');
        }
    }

    // Load new responses
    async function loadNewResponses() {
        const container = document.getElementById('responses-container');
        container.innerHTML = '<div class="text-center py-8"><div class="animate-spin w-8 h-8 border-4 border-purple-500 border-t-transparent rounded-full mx-auto"></div></div>';

        try {
            const response = await fetch('/stock/my-requests?status=all');
            const data = await response.json();

            if (data.success) {
                const responsesNeeded = data.data.filter(r => r.status !== 'pending' && r.cashier_response === 'pending');

                if (responsesNeeded.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-16">
                            <svg class="w-20 h-20 mx-auto text-gray-300 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-gray-600">No New Responses</h3>
                            <p class="text-gray-400 mt-1">All caught up! No pending responses to review.</p>
                        </div>
                    `;
                } else {
                    let tableRows = responsesNeeded.map(req => `
                        <tr class="hover:bg-purple-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-purple-600 font-mono font-semibold">${req.request_number}</span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">${req.items.length} items</span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium">Response Needed</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm">
                                ${new Date(req.responded_at).toLocaleString()}
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <button onclick="viewRequestDetails(${req.id})" class="p-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white rounded-lg transition" title="View Details">
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
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Request #</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Items</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    ${tableRows}
                                </tbody>
                            </table>
                        </div>
                    `;

                    // Update badge
                    document.getElementById('new-responses-badge').textContent = responsesNeeded.length;
                    document.getElementById('new-responses-badge').classList.remove('hidden');
                }
            }
        } catch (error) {
            container.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load responses</div>';
        }
    }

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateRequestModal();
            closeViewRequestModal();
        }
    });
</script>
@endpush