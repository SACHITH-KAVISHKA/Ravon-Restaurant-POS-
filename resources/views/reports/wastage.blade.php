@extends('layouts.app')

@section('title', 'Wastage Report')

@push('styles')
<style>
    .form-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
    }

    /* Summary cards */
    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .summary-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    .summary-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Report table */
    .report-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .report-table th {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        padding: 0.75rem 1rem;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .report-table th:first-child {
        border-radius: 0.5rem 0 0 0;
    }

    .report-table th:last-child {
        border-radius: 0 0.5rem 0 0;
    }

    .report-table td {
        padding: 0.6rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        font-size: 0.8rem;
    }

    .report-table tbody tr:hover {
        background-color: #fef2f2;
    }

    /* Filter bar */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }

    .filter-group label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        background-color: white;
        transition: all 0.2s ease;
        min-width: 140px;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }

    /* Badge styles */
    .badge-fg {
        background-color: #dcfce7;
        color: #166534;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 700;
    }

    .badge-rm {
        background-color: #fef3c7;
        color: #92400e;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 700;
    }

    .badge-reason {
        background-color: #f3f4f6;
        color: #374151;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 600;
    }

    /* Loading state */
    .loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 20;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #fecaca;
        border-top-color: #ef4444;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Top items */
    .top-item-bar {
        height: 8px;
        border-radius: 4px;
        transition: width 0.5s ease;
    }

    /* Reason breakdown */
    .reason-bar {
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        padding: 0 10px;
        font-size: 11px;
        font-weight: 600;
        color: white;
        transition: width 0.5s ease;
    }

    /* Toast */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        font-size: 14px;
        z-index: 99999;
        transform: translateX(120%);
        transition: transform 0.3s ease;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        max-width: 400px;
    }

    .toast.show {
        transform: translateX(0);
    }

    .toast-success {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }

    .toast-error {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    }

    /* Empty state */
    .empty-state-icon {
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
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
                <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-orange-500">
                    Wastage Report</h1>
                <p class="text-gray-500 text-sm">View and analyze stock wastage across all cashiers</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="summary-card">
                <div class="flex items-center gap-4">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #fef2f2, #fee2e2);">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Total Wastage</p>
                        <p class="text-xl font-bold text-red-600" id="summaryTotalAmount">Rs. 0.00</p>
                    </div>
                </div>
            </div>

            <div class="summary-card">
                <div class="flex items-center gap-4">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Total Records</p>
                        <p class="text-xl font-bold text-blue-600" id="summaryTotalItems">0</p>
                    </div>
                </div>
            </div>

            <div class="summary-card">
                <div class="flex items-center gap-4">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">FG Wastage</p>
                        <p class="text-lg font-bold text-green-600" id="summaryFgAmount">Rs. 0.00</p>
                        <p class="text-xs text-gray-400"><span id="summaryFgCount">0</span> records</p>
                    </div>
                </div>
            </div>

            <div class="summary-card">
                <div class="flex items-center gap-4">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #fffbeb, #fef3c7);">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">RM Wastage</p>
                        <p class="text-lg font-bold text-yellow-600" id="summaryRmAmount">Rs. 0.00</p>
                        <p class="text-xs text-gray-400"><span id="summaryRmCount">0</span> records</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="filter-bar">
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" id="fromDate">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" id="toDate">
                </div>
                <div class="filter-group">
                    <label>Item Type</label>
                    <select id="filterItemType">
                        <option value="all">All Types</option>
                        <option value="finished_good">Finished Goods (FG)</option>
                        <option value="raw_material">Raw Materials (RM)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Reason</label>
                    <select id="filterReason">
                        <option value="all">All Reasons</option>
                        @foreach($reasons as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Item</label>
                    <select id="filterItem">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                            <option value="{{ $item['id'] }}">{{ $item['code'] }} - {{ $item['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button id="applyFilterBtn"
                        class="px-5 py-2 bg-gradient-to-r from-red-500 to-orange-500 text-white font-semibold rounded-lg hover:shadow-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative">
            <div class="px-6 py-4 bg-gradient-to-r from-red-500 to-orange-500 flex justify-between items-center">
                <h2 class="text-lg font-bold text-white">Wastage Records</h2>
                <span class="text-white/80 text-sm" id="recordCount">0 records</span>
            </div>

            <div id="loadingOverlay" class="loading-overlay" style="display:none;">
                <div class="spinner"></div>
            </div>

            <div class="overflow-x-auto" style="max-height: 600px; overflow-y: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Wastage ID</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th class="text-center">Qty Before</th>
                            <th class="text-center">Qty Wasted</th>
                            <th class="text-center">Qty After</th>
                            <th class="text-center">Price</th>
                            <th class="text-center">Amount</th>
                            <th>Reason</th>
                            <th>Notes</th>
                            <th>Cashier</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <tr>
                            <td colspan="13" class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 empty-state-icon mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-400 text-sm">Select date range and click Search to view wastage data</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast"></div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default dates (today)
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fromDate').value = today;
        document.getElementById('toDate').value = today;

        const reasonColors = [
            '#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#8b5cf6', '#ec4899', '#64748b'
        ];

        // Apply filter
        document.getElementById('applyFilterBtn').addEventListener('click', loadData);

        // Auto-load on page load
        loadData();

        async function loadData() {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const itemType = document.getElementById('filterItemType').value;
            const reason = document.getElementById('filterReason').value;
            const itemId = document.getElementById('filterItem').value;

            const params = new URLSearchParams();
            if (fromDate) params.append('from_date', fromDate);
            if (toDate) params.append('to_date', toDate);
            if (itemType !== 'all') params.append('item_type', itemType);
            if (reason !== 'all') params.append('reason', reason);
            if (itemId) params.append('item_id', itemId);

            // Show loading
            document.getElementById('loadingOverlay').style.display = 'flex';

            try {
                const response = await fetch(`{{ route('wastage-report.data') }}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    }
                });

                const result = await response.json();

                if (result.success) {
                    updateSummary(result.summary);
                    renderTable(result.data);
                    document.getElementById('recordCount').textContent = `${result.total} records`;
                } else {
                    showToast('Failed to load data', 'error');
                }
            } catch (err) {
                showToast('Error loading data: ' + err.message, 'error');
            } finally {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        }

        function updateSummary(summary) {
            document.getElementById('summaryTotalAmount').textContent = 'Rs. ' + summary.total_amount;
            document.getElementById('summaryTotalItems').textContent = summary.total_items;
            document.getElementById('summaryFgAmount').textContent = 'Rs. ' + summary.fg_amount;
            document.getElementById('summaryFgCount').textContent = summary.fg_count;
            document.getElementById('summaryRmAmount').textContent = 'Rs. ' + summary.rm_amount;
            document.getElementById('summaryRmCount').textContent = summary.rm_count;
        }

        function renderTable(data) {
            const tbody = document.getElementById('reportTableBody');

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="13" class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-300 empty-state-icon mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-gray-400 text-sm">No wastage records found for the selected filters</p>
                        </td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = data.map((r, i) => `
                <tr class="hover:bg-red-50/50">
                    <td class="text-gray-400 font-medium">${i + 1}</td>
                    <td class="text-gray-600 text-xs whitespace-nowrap">${r.date_display}</td>
                    <td><span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-mono rounded">${r.wastage_id}</span></td>
                    <td>
                        <div class="font-semibold text-gray-800">${r.item_name}</div>
                        <div class="text-xs text-gray-400">${r.item_code}</div>
                    </td>
                    <td><span class="${r.item_type === 'finished_good' ? 'badge-fg' : 'badge-rm'}">${r.item_type_label}</span></td>
                    <td class="text-center text-gray-600">${r.quantity_before} ${r.unit}</td>
                    <td class="text-center text-red-600 font-bold">-${r.quantity_wasted} ${r.unit}</td>
                    <td class="text-center text-gray-600">${r.quantity_after} ${r.unit}</td>
                    <td class="text-center text-indigo-600 font-semibold">${r.price}</td>
                    <td class="text-center text-red-600 font-bold">Rs. ${r.wastage_amount}</td>
                    <td><span class="badge-reason">${r.reason_label}</span></td>
                    <td class="text-gray-500 text-xs max-w-[120px] truncate" title="${r.notes}">${r.notes}</td>
                    <td class="text-gray-600 text-xs">${r.performed_by}</td>
                </tr>
            `).join('');
        }



        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast toast-${type} show`;
            setTimeout(() => { toast.classList.remove('show'); }, 4000);
        }
    });
</script>
@endpush
