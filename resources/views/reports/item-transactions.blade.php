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
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        transition: all 0.2s ease;
    }

    .tx-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25);
    }

    .tx-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .tx-table th {
        background: linear-gradient(135deg, #5b7ce0 0%, #6476df 100%);
        color: #ffffff;
        font-weight: 700;
        padding: 12px 10px;
        text-align: left;
        font-size: 14px;
    }

    .tx-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 15px;
        color: #111827;
    }

    .tx-table tbody tr:hover {
        background: #f8fafc;
    }

    .qty-plus {
        color: #059669;
        font-weight: 700;
    }

    .qty-minus {
        color: #dc2626;
        font-weight: 700;
    }

    .qty-zero {
        color: #6b7280;
        font-weight: 700;
    }

    .row-opening {
        background: #e5e7eb;
        font-weight: 700;
    }

    .type-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 2px 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .type-sale_deduct { background: #fee2e2; color: #991b1b; }
    .type-sale_restore { background: #dbeafe; color: #1e40af; }
    .type-transfer_in { background: #dcfce7; color: #166534; }
    .type-adjustment { background: #fef3c7; color: #92400e; }
    .type-void_restore { background: #ede9fe; color: #5b21b6; }
    .type-wastage { background: #ffedd5; color: #9a3412; }
</style>
@endpush

@section('content')
<div class="flex min-h-screen bg-gray-100">
    <x-sidebar />

    <div class="flex-1 p-6">
        <div class="mb-6">
            <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">
                Item Transaction Details
            </h1>
            <p class="text-gray-600 mt-1">Track opening balance, transfers, sales, and all stock movements by item.</p>
        </div>

        <div class="tx-card p-5 mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Filter Transactions</h2>
            <form id="transactionFilterForm" class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                @csrf
                <div>
                    <label for="branch_id" class="block text-sm font-semibold text-gray-600 mb-1">Branch</label>
                    <select id="branch_id" name="branch_id" class="tx-input">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="item_id" class="block text-sm font-semibold text-gray-600 mb-1">Item</label>
                    <select id="item_id" name="item_id" class="tx-input" required>
                        <option value="">Select Item</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->item_name }} ({{ $item->item_code ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="from_date" class="block text-sm font-semibold text-gray-600 mb-1">From Date</label>
                    <input id="from_date" name="from_date" type="date" class="tx-input" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-semibold text-gray-600 mb-1">To Date</label>
                    <input id="to_date" name="to_date" type="date" class="tx-input" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="tx-btn w-full">Generate</button>
                </div>
            </form>
        </div>

        <div class="tx-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 id="historyTitle" class="text-2xl font-bold text-gray-800">Transaction History</h3>
                <p id="historySubTitle" class="text-gray-600 mt-1">Select filters and click Generate to view details.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>Date and Time</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Performed By</th>
                            <th class="text-right">Quantity</th>
                        </tr>
                    </thead>
                    <tbody id="transactionBody">
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-10">Select filters and click Generate.</td>
                        </tr>
                    </tbody>
                </table>
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
                <td colspan="5" class="text-center text-gray-500 py-10">Loading transactions...</td>
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
                    <td colspan="5" class="text-center text-red-600 py-10">${escapeHtml(error.message)}</td>
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
                <td>Before ${meta.from_date}</td>
                <td>Opening Balance</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-right qty-zero">${escapeHtml(meta.opening_display)}</td>
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
                    <tr>
                        <td>${escapeHtml(row.date_time)}</td>
                        <td><span class="type-pill ${typeClass}">${escapeHtml(row.type)}</span></td>
                        <td>${escapeHtml(row.reference)}</td>
                        <td>${escapeHtml(row.performed_by)}</td>
                        <td class="text-right ${qtyClass}">${escapeHtml(row.quantity_display)}</td>
                    </tr>
                `;
            });
        }

        html += `
            <tr class="bg-gray-50 font-semibold">
                <td colspan="4" class="text-right">Closing Balance</td>
                <td class="text-right">${escapeHtml(meta.closing_display)}</td>
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
