@extends('layouts.app')

@section('title', 'Item Transaction Details')

@push('styles')
<style>
    .tx-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .tx-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 14px;
        color: #111827;
        background-color: #ffffff;
    }

    .tx-input:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    .tx-btn {
        border: 0;
        border-radius: 10px;
        padding: 10px 18px;
        color: #ffffff;
        font-weight: 600;
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
            <form id="transactionFilterForm" class="grid grid-cols-1 lg:grid-cols-5 gap-4">
    .type-sale_deduct { background: #fee2e2; color: #991b1b; }
    .type-sale_restore { background: #dbeafe; color: #1e40af; }
    .type-transfer_in { background: #dcfce7; color: #166534; }
    .type-adjustment { background: #fef3c7; color: #92400e; }
    .type-void_restore { background: #ede9fe; color: #5b21b6; }
    .type-wastage { background: #ffedd5; color: #9a3412; }
</style>
@endpush

@section('content')
<div class="flex h-screen overflow-hidden">
    <x-sidebar />

    <div class="flex-1 overflow-y-auto">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">Item Transaction Details</h1>
                <p class="text-gray-600">Track opening balance, transfers, sales, and all stock movements by item.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
            <form id="transactionFilterForm" class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                @csrf
                <div>
                    <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <select id="branch_id" name="branch_id" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="item_id" class="block text-sm font-medium text-gray-700 mb-2">Item</label>
                    <select id="item_id" name="item_id" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                        <option value="">Select Item</option>
                        <optgroup label="Finished Goods">
                            @foreach($items->where('item_type', 'finished_good') as $item)
                                <option value="{{ $item->id }}">{{ $item->item_name }} ({{ $item->item_code ?? 'N/A' }})</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Raw Materials">
                            @foreach($items->where('item_type', 'raw_material') as $item)
                                <option value="{{ $item->id }}">{{ $item->item_name }} ({{ $item->item_code ?? 'N/A' }})</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input id="from_date" name="from_date" type="date" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input id="to_date" name="to_date" type="date" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center w-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Search
                    </button>
                </div>
            </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                

                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Date and Time</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Performed By</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="transactionBody" class="divide-y divide-gray-200">
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 py-10">Select filters and click Search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const filterForm = document.getElementById('transactionFilterForm');
    const transactionBody = document.getElementById('transactionBody');
    const historyTitle = document.getElementById('historyTitle');
    const historySubTitle = document.getElementById('historySubTitle');

    filterForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        await loadTransactionData();
    });

    async function loadTransactionData() {
        const formData = new FormData(filterForm);

        transactionBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                        <p class="text-gray-600">Loading transactions...</p>
                    </div>
                </td>
            </tr>
        `;

        try {
            const response = await fetch("{{ route('reports.item-transactions.data') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                const message = payload.message || 'Failed to load transaction data.';
                throw new Error(message);
            }

            renderTransactions(payload.meta, payload.transactions);
        } catch (error) {
            transactionBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-10 text-red-600">${escapeHtml(error.message)}</td>
                </tr>
            `;
        }
    }

    function renderTransactions(meta, transactions) {
        const branchLabel = meta.branch_name ? meta.branch_name : 'All Branches';
        const itemLabel = `${meta.item_name} (${meta.item_code || 'N/A'})`;

        historyTitle.textContent = `Transaction History for ${itemLabel}`;
        historySubTitle.textContent = `${branchLabel} | ${meta.from_date} to ${meta.to_date} | Unit: ${meta.unit}`;

        let html = `
            <tr class="row-opening">
                <td class="px-6 py-4">Before ${meta.from_date}</td>
                <td class="px-6 py-4">Opening Balance</td>
                <td class="px-6 py-4 text-center">-</td>
                <td class="px-6 py-4 text-center">-</td>
                <td class="px-6 py-4 text-right qty-zero">${escapeHtml(meta.opening_display)}</td>
            </tr>
        `;

        if (transactions.length === 0) {
            html += `
                <tr>
                    <td colspan="5" class="text-center text-gray-500 py-8">No transactions found in selected date range.</td>
                </tr>
            `;
        } else {
            transactions.forEach((row) => {
                const qtyClass = row.quantity_changed > 0 ? 'qty-plus' : (row.quantity_changed < 0 ? 'qty-minus' : 'qty-zero');
                const typeClass = `type-${row.type_key}`;

                html += `
                    <tr class="hover:bg-purple-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">${escapeHtml(row.date_time)}</td>
                        <td class="px-6 py-4"><span class="type-pill ${typeClass}">${escapeHtml(row.type)}</span></td>
                        <td class="px-6 py-4 text-gray-700">${escapeHtml(row.reference)}</td>
                        <td class="px-6 py-4 text-gray-700">${escapeHtml(row.performed_by)}</td>
                        <td class="px-6 py-4 text-right ${qtyClass}">${escapeHtml(row.quantity_display)}</td>
                    </tr>
                `;
            });
        }

        html += `
            <tr class="bg-gray-100 font-semibold border-t border-gray-300">
                <td colspan="4" class="px-6 py-4 text-right uppercase tracking-wider text-gray-800">Closing Balance</td>
                <td class="px-6 py-4 text-right text-gray-900">${escapeHtml(meta.closing_display)}</td>
            </tr>
        `;

        transactionBody.innerHTML = html;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endpush
