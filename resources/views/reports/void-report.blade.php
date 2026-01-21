@extends('layouts.app')

@section('title', 'Void Report - Ravon Restaurant POS')

@push('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .btn-action {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
        }

        .summary-card-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.7);
        }
    </style>
@endpush

@section('content')
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1
                        class="text-3xl font-bold bg-gradient-to-r from-[#ef4444] to-[#dc2626] bg-clip-text text-transparent mb-2">
                        Void Report</h1>
                    <p class="text-gray-600">Track and analyze voided items and transactions</p>
                </div>

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form method="GET" action="{{ route('void-report.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date
                                </label>
                                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    End Date
                                </label>
                                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>

                            <!-- Supervisor Filter -->
                            <div>
                                <label for="supervisor_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Supervisor
                                </label>
                                <select id="supervisor_id" name="supervisor_id"
                                    class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                    <option value="">All Supervisors</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}" {{ $supervisorId == $supervisor->id ? 'selected' : '' }}>
                                            {{ $supervisor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                            <!-- Actions -->
                            <div class="flex items-end space-x-2">
                                <button type="submit"
                                    class="flex-1 bg-gradient-to-r from-[#ef4444] to-[#dc2626] hover:shadow-lg hover:shadow-red-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Search
                                </button>
                                <a href="{{ route('void-report.export', request()->query()) }}"
                                    class="bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Export
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Void Records Table -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="table-responsive">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-[#ef4444] to-[#dc2626]">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Void #</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Date/Time</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Order #</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Item</th>
                                    <th
                                        class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                        Qty</th>
                                    <th
                                        class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">
                                        Amount</th>
                                    <th
                                        class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($voidRecords as $record)
                                    <tr class="hover:bg-red-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-red-600 font-mono font-semibold">{{ $record->void_number }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm">
                                            {{ $record->created_at->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-purple-600 font-mono">{{ $record->order_number }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-gray-800 font-medium">{{ $record->item_name }}</div>
                                            @if($record->item_code)
                                                <div class="text-gray-500 text-xs">{{ $record->item_code }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-700 font-bold rounded-full">
                                                {{ $record->voided_quantity }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-red-600">
                                            Rs. {{ number_format($record->voided_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <button
                                                class="btn-action bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white view-details-btn"
                                                data-void-id="{{ $record->id }}" title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="text-gray-600 text-lg">No void records found for the selected filters
                                                </p>
                                                <p class="text-gray-500 text-sm mt-1">Great! No items have been voided in this
                                                    period.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Void Details Modal -->
    <div class="modal fade hidden fixed inset-0 z-50 overflow-y-auto" id="voidDetailsModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="modal-backdrop fixed inset-0" onclick="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 max-w-lg w-full">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-red-100">
                    <h3 class="text-xl font-bold text-red-700">Void Details</h3>
                    <button class="text-gray-500 hover:text-gray-700" onclick="closeModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4" id="modal-content">
                    <!-- Loading state -->
                    <div id="modal-loading" class="flex items-center justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>

                    <!-- Details content -->
                    <div id="modal-details" class="hidden space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Void Number</p>
                                <p class="text-lg font-semibold text-red-600" id="detail-void-number">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Order Number</p>
                                <p class="text-lg font-semibold text-purple-600" id="detail-order-number">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Date & Time</p>
                                <p class="text-lg font-semibold text-gray-800" id="detail-datetime">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Table</p>
                                <p class="text-lg font-semibold text-gray-800" id="detail-table">-</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-medium text-gray-600 mb-2">Item Details</h4>
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-800" id="detail-item-name">-</p>
                                        <p class="text-sm text-gray-500" id="detail-item-code">-</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-600">Quantity Voided</p>
                                        <p class="text-2xl font-bold text-red-600" id="detail-quantity">-</p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-red-200 flex justify-between">
                                    <span class="text-gray-600">Unit Price:</span>
                                    <span class="font-semibold" id="detail-unit-price">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Voided Amount:</span>
                                    <span class="font-bold text-red-600 text-lg" id="detail-amount">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-medium text-gray-600 mb-2">Authorization</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Cashier</p>
                                    <p class="font-semibold text-gray-800" id="detail-cashier">-</p>
                                </div>
                                <div class="bg-orange-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Supervisor</p>
                                    <p class="font-semibold text-orange-700" id="detail-supervisor">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Cancel KOT</p>
                                    <p class="font-mono text-gray-800" id="detail-cancel-kot">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Reason</p>
                                    <p class="text-gray-800" id="detail-reason">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button onclick="closeModal()"
                        class="bg-white hover:bg-gray-100 text-gray-700 font-semibold py-2 px-6 rounded-lg transition border border-gray-300">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function () {
                // CSRF Token setup
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                // View Details Button
                $('.view-details-btn').on('click', function () {
                    const voidId = $(this).data('void-id');

                    // Show modal with loading
                    $('#voidDetailsModal').removeClass('hidden');
                    $('#modal-loading').removeClass('hidden');
                    $('#modal-details').addClass('hidden');

                    $.get(`/void-report/${voidId}`)
                        .done(function (data) {
                            // Populate modal
                            $('#detail-void-number').text(data.void_number);
                            $('#detail-order-number').text(data.order_number);
                            $('#detail-datetime').text(data.created_at);
                            $('#detail-table').text(data.table_number);
                            $('#detail-item-name').text(data.item_name);
                            $('#detail-item-code').text(data.item_code || 'N/A');
                            $('#detail-quantity').text(data.voided_quantity);
                            $('#detail-unit-price').text('Rs. ' + data.unit_price);
                            $('#detail-amount').text('Rs. ' + data.voided_amount);
                            $('#detail-cashier').text(data.cashier_name);
                            $('#detail-supervisor').text(data.supervisor_name);
                            $('#detail-cancel-kot').text(data.cancel_kot_number);
                            $('#detail-reason').text(data.reason);

                            // Show details
                            $('#modal-loading').addClass('hidden');
                            $('#modal-details').removeClass('hidden');
                        })
                        .fail(function () {
                            alert('Failed to load void details');
                            closeModal();
                        });
                });
            });

            function closeModal() {
                $('#voidDetailsModal').addClass('hidden');
            }

            // Close modal on escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        </script>
    @endpush
@endsection