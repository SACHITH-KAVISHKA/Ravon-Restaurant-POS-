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

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .request-card {
        transition: all 0.3s ease;
    }

    .request-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
                <h1 class="text-2xl font-bold text-gray-800">Stock Transfer Requests</h1>
                <p class="text-gray-500 text-sm">Review and respond to cashier stock requests</p>
            </div>
        </div>

        <!-- Tabs Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-100 px-6">
                <div class="flex gap-6">
                    <button onclick="switchTab('pending')" id="tab-pending" class="tab-btn active py-4 font-medium text-sm flex items-center gap-2">
                        <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                        Pending
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
                    @if($pendingRequests->isEmpty())
                    <div class="text-center py-16">
                        <svg class="w-24 h-24 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-500">No Pending Transfers</h3>
                        <p class="text-gray-400 mt-1">There are no pending transfers at the moment.</p>
                    </div>
                    @else
                    <div class="space-y-4">
                        @foreach($pendingRequests as $request)
                        <div class="request-card bg-white rounded-lg border border-gray-200 p-5 hover:shadow-lg cursor-pointer" onclick="viewRequest({{ $request->id }})">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">{{ $request->request_number }}</h3>
                                        <p class="text-sm text-gray-500">From: {{ $request->cashier->name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">Pending</span>
                                    <p class="text-xs text-gray-400 mt-2">{{ $request->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($request->items->take(4) as $item)
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full">
                                    {{ $item->item_name }} ({{ $item->requested_quantity }})
                                </span>
                                @endforeach
                                @if($request->items->count() > 4)
                                <span class="px-3 py-1 bg-purple-100 text-purple-600 text-sm rounded-full font-medium">
                                    +{{ $request->items->count() - 4 }} more
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Accepted Tab -->
                <div id="content-accepted" class="tab-content hidden">
                    @if($acceptedRequests->isEmpty())
                    <div class="text-center py-16">
                        <svg class="w-24 h-24 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-500">No Accepted Transfers</h3>
                        <p class="text-gray-400 mt-1">No transfers have been accepted yet.</p>
                    </div>
                    @else
                    <div class="space-y-4">
                        @foreach($acceptedRequests as $request)
                        <div class="request-card bg-white rounded-lg border border-gray-200 p-5 hover:shadow-lg cursor-pointer" onclick="viewRequest({{ $request->id }})">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">{{ $request->request_number }}</h3>
                                        <p class="text-sm text-gray-500">From: {{ $request->cashier->name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 {{ $request->status === 'partially_accepted' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }} rounded-full text-sm font-medium">
                                        {{ $request->status === 'partially_accepted' ? 'Partially Accepted' : 'Accepted' }}
                                    </span>
                                    <p class="text-xs text-gray-400 mt-2">{{ $request->responded_at ? $request->responded_at->diffForHumans() : '' }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($request->items->take(4) as $item)
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full">
                                    {{ $item->item_name }} ({{ $item->approved_quantity ?? $item->requested_quantity }})
                                </span>
                                @endforeach
                                @if($request->items->count() > 4)
                                <span class="px-3 py-1 bg-purple-100 text-purple-600 text-sm rounded-full font-medium">
                                    +{{ $request->items->count() - 4 }} more
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Rejected Tab -->
                <div id="content-rejected" class="tab-content hidden">
                    @if($rejectedRequests->isEmpty())
                    <div class="text-center py-16">
                        <svg class="w-24 h-24 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-500">No Rejected Transfers</h3>
                        <p class="text-gray-400 mt-1">No transfers have been rejected.</p>
                    </div>
                    @else
                    <div class="space-y-4">
                        @foreach($rejectedRequests as $request)
                        <div class="request-card bg-white rounded-lg border border-gray-200 p-5 hover:shadow-lg cursor-pointer" onclick="viewRequest({{ $request->id }})">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">{{ $request->request_number }}</h3>
                                        <p class="text-sm text-gray-500">From: {{ $request->cashier->name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Rejected</span>
                                    <p class="text-xs text-gray-400 mt-2">{{ $request->responded_at ? $request->responded_at->diffForHumans() : '' }}</p>
                                </div>
                            </div>
                            @if($request->supervisor_notes)
                            <p class="text-sm text-gray-500 italic">{{ Str::limit($request->supervisor_notes, 100) }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View/Respond Request Modal -->
<div id="requestModal" class="fixed inset-0 bg-black/50 modal-backdrop z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-600 to-purple-700">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-white" id="modal-request-number">Request Details</h2>
                    <p class="text-purple-200 text-sm" id="modal-request-info"></p>
                </div>
                <button onclick="closeRequestModal()" class="text-white/80 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
            <!-- Status Badge -->
            <div class="mb-4" id="modal-status-container">
                <!-- Status badge will be inserted here -->
            </div>

            <!-- Notes Section -->
            <div id="modal-notes-section" class="mb-6 hidden">
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600"><strong>Request Notes:</strong> <span id="modal-request-notes"></span></p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h3 class="font-semibold text-gray-700 mb-4">Requested Items</h3>
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500 border-b border-gray-200">
                            <th class="pb-3">Item</th>
                            <th class="pb-3 text-center">Requested</th>
                            <th class="pb-3 text-center" id="approved-header">Approve Qty</th>
                            <th class="pb-3 text-center" id="status-header" style="display: none;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="modal-items-body">
                        <!-- Items will be inserted here -->
                    </tbody>
                </table>
            </div>

            <!-- Supervisor Notes (for responding) -->
            <div class="mt-6" id="supervisor-notes-container">
                <label class="block text-sm font-medium text-gray-700 mb-2">Supervisor Notes (Optional)</label>
                <textarea id="supervisor-notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none" placeholder="Add notes for the cashier..."></textarea>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50" id="modal-footer">
            <div class="flex gap-3">
                <button onclick="rejectRequest()" id="reject-btn" class="flex-1 px-6 py-3 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Reject All
                </button>
                <button onclick="acceptRequest()" id="accept-btn" class="flex-1 px-6 py-3 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Approve & Respond
                </button>
            </div>
        </div>

        <!-- View-only Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 hidden" id="modal-footer-view">
            <button onclick="closeRequestModal()" class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                Close
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let currentRequest = null;

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
    }

    // View request details
    async function viewRequest(requestId) {
        try {
            const response = await fetch(`/stock/${requestId}`);
            const data = await response.json();

            if (data.success) {
                currentRequest = data.data;
                renderRequestModal(data.data);
                document.getElementById('requestModal').classList.remove('hidden');
            }
        } catch (error) {
            showToast('Failed to load request details', 'error');
        }
    }

    function renderRequestModal(request) {
        document.getElementById('modal-request-number').textContent = request.request_number;
        document.getElementById('modal-request-info').textContent = `From: ${request.cashier.name} • ${new Date(request.created_at).toLocaleString()}`;

        // Status badge
        let statusHtml = '';
        switch (request.status) {
            case 'pending':
                statusHtml = '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">Pending Review</span>';
                break;
            case 'accepted':
                statusHtml = '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Accepted</span>';
                break;
            case 'rejected':
                statusHtml = '<span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Rejected</span>';
                break;
            case 'partially_accepted':
                statusHtml = '<span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">Partially Accepted</span>';
                break;
        }
        document.getElementById('modal-status-container').innerHTML = statusHtml;

        // Notes
        if (request.notes) {
            document.getElementById('modal-notes-section').classList.remove('hidden');
            document.getElementById('modal-request-notes').textContent = request.notes;
        } else {
            document.getElementById('modal-notes-section').classList.add('hidden');
        }

        // Items
        const isPending = request.status === 'pending';
        document.getElementById('approved-header').style.display = isPending ? '' : 'none';
        document.getElementById('status-header').style.display = isPending ? 'none' : '';

        document.getElementById('modal-items-body').innerHTML = request.items.map(item => {
            if (isPending) {
                return `
                    <tr class="border-b border-gray-100" data-item-id="${item.id}">
                        <td class="py-3 font-medium text-gray-700">${item.item_name}</td>
                        <td class="py-3 text-center text-gray-600">${item.requested_quantity}</td>
                        <td class="py-3">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="adjustQuantity(${item.id}, -1)" class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 hover:bg-gray-300 transition flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </button>
                                <input type="number" value="${item.requested_quantity}" min="0" max="${item.requested_quantity}"
                                    id="qty-${item.id}"
                                    class="quantity-input w-16 text-center border border-gray-200 rounded-lg py-1 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    onchange="validateQuantity(${item.id}, ${item.requested_quantity})">
                                <button onclick="adjustQuantity(${item.id}, 1, ${item.requested_quantity})" class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 hover:bg-purple-200 transition flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                let statusBadge = '';
                switch (item.status) {
                    case 'approved':
                        statusBadge = '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">Approved</span>';
                        break;
                    case 'rejected':
                        statusBadge = '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded">Rejected</span>';
                        break;
                    case 'partially_approved':
                        statusBadge = '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded">Partial</span>';
                        break;
                    default:
                        statusBadge = '<span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">Pending</span>';
                }
                return `
                    <tr class="border-b border-gray-100">
                        <td class="py-3 font-medium text-gray-700">${item.item_name}</td>
                        <td class="py-3 text-center text-gray-600">${item.requested_quantity}</td>
                        <td class="py-3 text-center">
                            ${item.approved_quantity !== null ? `<span class="font-medium text-green-600">${item.approved_quantity}</span>` : '-'}
                            ${statusBadge}
                        </td>
                    </tr>
                `;
            }
        }).join('');

        // Show/hide appropriate footer and notes input
        if (isPending) {
            document.getElementById('modal-footer').classList.remove('hidden');
            document.getElementById('modal-footer-view').classList.add('hidden');
            document.getElementById('supervisor-notes-container').classList.remove('hidden');
        } else {
            document.getElementById('modal-footer').classList.add('hidden');
            document.getElementById('modal-footer-view').classList.remove('hidden');
            document.getElementById('supervisor-notes-container').classList.add('hidden');
        }
    }

    function adjustQuantity(itemId, delta, max = null) {
        const input = document.getElementById(`qty-${itemId}`);
        let newVal = parseInt(input.value) + delta;
        if (newVal < 0) newVal = 0;
        if (max !== null && newVal > max) newVal = max;
        input.value = newVal;
    }

    function validateQuantity(itemId, max) {
        const input = document.getElementById(`qty-${itemId}`);
        let val = parseInt(input.value);
        if (isNaN(val) || val < 0) val = 0;
        if (val > max) val = max;
        input.value = val;
    }

    function closeRequestModal() {
        document.getElementById('requestModal').classList.add('hidden');
        currentRequest = null;
        document.getElementById('supervisor-notes').value = '';
    }

    // Accept request
    async function acceptRequest() {
        if (!currentRequest) return;

        const items = [];
        currentRequest.items.forEach(item => {
            const input = document.getElementById(`qty-${item.id}`);
            items.push({
                id: item.id,
                approved_quantity: parseInt(input.value)
            });
        });

        const btn = document.getElementById('accept-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

        try {
            const response = await fetch(`/stock/${currentRequest.id}/supervisor-respond`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify({
                    action: 'accept',
                    items: items,
                    supervisor_notes: document.getElementById('supervisor-notes').value
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Request approved successfully!', 'success');
                closeRequestModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to approve request', 'error');
            }
        } catch (error) {
            showToast('An error occurred. Please try again.', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Approve & Respond';
    }

    // Reject request
    async function rejectRequest() {
        if (!currentRequest) return;

        const btn = document.getElementById('reject-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

        try {
            const response = await fetch(`/stock/${currentRequest.id}/supervisor-respond`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify({
                    action: 'reject',
                    supervisor_notes: document.getElementById('supervisor-notes').value
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Request rejected', 'success');
                closeRequestModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to reject request', 'error');
            }
        } catch (error) {
            showToast('An error occurred. Please try again.', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg> Reject All';
    }

    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRequestModal();
        }
    });
</script>
@endpush