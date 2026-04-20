@extends('layouts.app')

@section('title', 'Incoming Stock Transfers - Cashier')

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

    .pulse-dot {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .5;
        }
    }

    .transfer-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .transfer-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .transfer-card.pending {
        border-left-color: #fbbf24;
    }

    .transfer-card.accepted {
        border-left-color: #10b981;
    }

    .transfer-card.rejected {
        border-left-color: #ef4444;
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
                <h1 class="text-2xl font-bold text-gray-800">Incoming Stock Transfers</h1>
                <p class="text-gray-500 text-sm">Accept raw materials from supervisor</p>
            </div>
            @if($counts['pending'] > 0)
            <div class="flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg">
                <span class="w-2 h-2 bg-yellow-500 rounded-full pulse-dot"></span>
                <span class="font-semibold">{{ $counts['pending'] }} Pending Transfer(s)</span>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <form method="GET" action="{{ route('stock-transfer.cashier.index') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="from_date" class="block text-sm font-semibold text-gray-600 mb-1">From Date</label>
                    <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-semibold text-gray-600 mb-1">To Date</label>
                    <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-5 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg font-semibold hover:shadow-lg transition">
                        Search
                    </button>
                    <a href="{{ route('stock-transfer.cashier.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Pending Transfers & History -->
            <div class="space-y-6">
                <!-- Tabs Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-100 px-6">
                        <div class="flex gap-6">
                            <button onclick="switchTab('pending')" id="tab-pending" class="tab-btn active py-4 font-medium text-sm flex items-center gap-2">
                                <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                                Pending
                                @if($counts['pending'] > 0)
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">{{ $counts['pending'] }}</span>
                                @endif
                            </button>
                            <button onclick="switchTab('accepted')" id="tab-accepted" class="tab-btn py-4 font-medium text-sm flex items-center gap-2 text-gray-500">
                                <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                Accepted
                            </button>
                            <button onclick="switchTab('rejected')" id="tab-rejected" class="tab-btn py-4 font-medium text-sm flex items-center gap-2 text-gray-500">
                                <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                Rejected
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- Pending Tab -->
                        <div id="content-pending" class="tab-content">
                            @if($pendingTransfers->isEmpty())
                            <div class="text-center py-12">
                                <svg class="w-20 h-20 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-semibold text-gray-500">No Pending Transfers</h3>
                                <p class="text-gray-400 mt-1">All caught up! No transfers waiting for your approval.</p>
                            </div>
                            @else
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Transfer #</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">From</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Items</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Date</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($pendingTransfers as $transfer)
                                        <tr class="hover:bg-yellow-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <span class="text-emerald-600 font-mono font-semibold">{{ $transfer->transfer_number }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                {{ $transfer->supervisor->name }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ $transfer->items->count() }} items</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">Pending</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-sm">
                                                {{ $transfer->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button data-transfer-id="{{ $transfer->id }}" class="view-transfer-btn p-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg transition" title="View & Respond">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            @endif
                        </div>

                        <!-- Accepted Tab -->
                        <div id="content-accepted" class="tab-content hidden">
                            @if($acceptedTransfers->isEmpty())
                            <div class="text-center py-12">
                                <svg class="w-20 h-20 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-semibold text-gray-500">No Accepted Transfers</h3>
                                <p class="text-gray-400 mt-1">No transfers have been accepted yet.</p>
                            </div>
                            @else
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Transfer #</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Items</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Date</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($acceptedTransfers as $transfer)
                                        <tr class="hover:bg-green-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <span class="text-emerald-600 font-mono font-semibold">{{ $transfer->transfer_number }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ $transfer->items->count() }} items</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-sm">
                                                {{ $transfer->responded_at ? $transfer->responded_at->format('M d, Y') : $transfer->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button data-transfer-id="{{ $transfer->id }}" class="view-transfer-btn p-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            @endif
                        </div>

                        <!-- Rejected Tab -->
                        <div id="content-rejected" class="tab-content hidden">
                            @if($rejectedTransfers->isEmpty())
                            <div class="text-center py-12">
                                <svg class="w-20 h-20 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-semibold text-gray-500">No Rejected Transfers</h3>
                                <p class="text-gray-400 mt-1">No transfers have been rejected.</p>
                            </div>
                            @else
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Transfer #</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Items</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Date</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($rejectedTransfers as $transfer)
                                        <tr class="hover:bg-red-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <span class="text-emerald-600 font-mono font-semibold">{{ $transfer->transfer_number }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ $transfer->items->count() }} items</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-sm">
                                                {{ $transfer->responded_at ? $transfer->responded_at->format('M d, Y') : $transfer->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button data-transfer-id="{{ $transfer->id }}" class="view-transfer-btn p-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View/Respond Transfer Modal -->
<div id="transferModal" class="fixed inset-0 bg-black/50 modal-backdrop z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-600 to-emerald-700">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-white" id="modal-transfer-number">Transfer Details</h2>
                    <p class="text-emerald-200 text-sm" id="modal-transfer-info"></p>
                </div>
                <button onclick="closeTransferModal()" class="text-white/80 hover:text-white transition">
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
                    <p class="text-sm text-gray-600"><strong>Notes:</strong> <span id="modal-transfer-notes"></span></p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h3 class="font-semibold text-gray-700 mb-4">Transfer Items</h3>
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500 border-b border-gray-200">
                            <th class="pb-3">Item</th>
                            <th class="pb-3 text-center">Quantity</th>
                            <th class="pb-3 text-center">Unit</th>
                        </tr>
                    </thead>
                    <tbody id="modal-items-body">
                        <!-- Items will be inserted here -->
                    </tbody>
                </table>
            </div>

            <!-- Response Notes (for pending transfers) -->
            <div class="mt-6 hidden" id="response-notes-container">
                <label class="block text-sm font-medium text-gray-700 mb-2">Response Notes (Optional)</label>
                <textarea id="response-notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none" placeholder="Add notes..."></textarea>
            </div>
        </div>

        <!-- Modal Footer - Respond Buttons -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50" id="modal-footer">
            <div class="flex gap-3">
                <button onclick="respondToTransfer('reject')" id="reject-btn" class="flex-1 px-6 py-3 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Reject
                </button>
                <button onclick="respondToTransfer('accept')" id="accept-btn" class="flex-1 px-6 py-3 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Accept Transfer
                </button>
            </div>
        </div>

        <!-- View-only Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 hidden" id="modal-footer-view">
            <button onclick="closeTransferModal()" class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                Close
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let currentTransfer = null;

    // Tab switching
    function switchTab(tab) {
        localStorage.setItem('stockTransferTab', tab);
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

    // View transfer details
    async function viewTransfer(transferId) {
        try {
            const response = await fetch(`/stock-transfer/${transferId}`);
            const data = await response.json();

            if (data.success) {
                currentTransfer = data.data;
                renderTransferModal(data.data);
                const modal = document.getElementById('transferModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        } catch (error) {
            showToast('Failed to load transfer details', 'error');
        }
    }

    function renderTransferModal(transfer) {
        document.getElementById('modal-transfer-number').textContent = transfer.transfer_number;
        document.getElementById('modal-transfer-info').textContent = `From: ${transfer.supervisor.name} • ${new Date(transfer.created_at).toLocaleString()}`;

        // Status badge
        let statusHtml = '';
        switch (transfer.status) {
            case 'pending':
                statusHtml = '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">Pending Acceptance</span>';
                break;
            case 'accepted':
                statusHtml = '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Accepted</span>';
                break;
            case 'rejected':
                statusHtml = '<span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Rejected</span>';
                break;
        }
        document.getElementById('modal-status-container').innerHTML = statusHtml;

        // Notes
        if (transfer.notes) {
            document.getElementById('modal-notes-section').classList.remove('hidden');
            document.getElementById('modal-transfer-notes').textContent = transfer.notes;
        } else {
            document.getElementById('modal-notes-section').classList.add('hidden');
        }

        // Items
        document.getElementById('modal-items-body').innerHTML = transfer.items.map(item => {
            return `
            <tr class="border-b border-gray-100">
                <td class="py-3 font-medium text-gray-700">${item.item_name}</td>
                <td class="py-3 text-center text-gray-600 font-semibold">${parseFloat(item.quantity).toFixed(3)}</td>
                <td class="py-3 text-center text-gray-500">${item.main_stock_item?.unit_abbreviation || 'pcs'}</td>
            </tr>
        `}).join('');

        // Show/hide appropriate footer
        const isPending = transfer.status === 'pending';
        if (isPending) {
            document.getElementById('modal-footer').classList.remove('hidden');
            document.getElementById('modal-footer-view').classList.add('hidden');
            document.getElementById('response-notes-container').classList.remove('hidden');
        } else {
            document.getElementById('modal-footer').classList.add('hidden');
            document.getElementById('modal-footer-view').classList.remove('hidden');
            document.getElementById('response-notes-container').classList.add('hidden');
        }
    }

    function closeTransferModal() {
        const modal = document.getElementById('transferModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentTransfer = null;
        document.getElementById('response-notes').value = '';
    }

    // Respond to transfer
    async function respondToTransfer(action) {
        if (!currentTransfer) return;

        const btn = document.getElementById(action === 'accept' ? 'accept-btn' : 'reject-btn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

        try {
            const response = await fetch(`/stock-transfer/${currentTransfer.id}/respond`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify({
                    action: action,
                    notes: document.getElementById('response-notes').value
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                closeTransferModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to respond', 'error');
            }
        } catch (error) {
            showToast('An error occurred. Please try again.', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }

    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTransferModal();
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
    document.addEventListener('DOMContentLoaded', function() {
        const savedTab = localStorage.getItem('stockTransferTab');
        const initialTab = savedTab && document.getElementById(`tab-${savedTab}`) ? savedTab : 'pending';

        switchTab(initialTab);
    });
</script>
@endpush