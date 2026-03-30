@extends('layouts.app')

@section('title', 'Edit Sale Information - Ravon POS')

@section('content')
<div class="min-h-screen bg-gray-50 flex">
    <x-sidebar />

    <div class="flex-1 py-6 w-full">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Sale Information
                </h1>

                <a href="{{ route('sales-report.index') }}"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Sales Report
                </a>
            </div>

            @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gradient-to-r from-[#1d7cf2] to-[#1d7cf2]">
                    <h2 class="text-xl font-semibold text-white">Update Receipt and Customer Details</h2>
                </div>

                <form action="{{ route('sales-report.update', $order) }}" method="POST" class="p-6 space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="order_number" class="block text-sm font-semibold text-gray-700 mb-2">Manual Order ID / Receipt Number</label>
                        <input type="text" id="order_number" name="order_number"
                            value="{{ old('order_number', $order->order_number) }}"
                            class="w-full px-4 py-2.5 text-base border border-gray-400 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>

                    <hr class="my-3 border-gray-300">

                    <div>
                        <label for="vat_customer_id" class="block text-sm font-semibold text-gray-700 mb-2">Select VAT Customer</label>
                        <select id="vat_customer_id" name="vat_customer_id"
                            class="w-full px-4 py-2.5 text-base border border-gray-400 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">No VAT Customer</option>
                            @foreach($vatCustomers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                data-vat-number="{{ $customer->vat_number }}"
                                {{ (string) old('vat_customer_id', $selectedVatCustomerId) === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->customer_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="customer_vat_number" class="block text-sm font-semibold text-gray-700 mb-2">Customer VAT Number</label>
                        <input type="text" id="customer_vat_number" name="customer_vat_number"
                            value="{{ old('customer_vat_number', $order->customer_vat_number) }}"
                            class="w-full px-4 py-2.5 text-base border border-gray-400 rounded-lg bg-gray-50"
                            readonly>
                    </div>

                    <button type="submit"
                        class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white text-2xl font-semibold rounded-lg transition flex items-center justify-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Update Sale Details
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const customerSelect = document.getElementById('vat_customer_id');
    const vatNumberInput = document.getElementById('customer_vat_number');

    function syncVatNumber() {
        const option = customerSelect.options[customerSelect.selectedIndex];
        vatNumberInput.value = option ? (option.getAttribute('data-vat-number') || '') : '';
    }

    customerSelect.addEventListener('change', syncVatNumber);
    syncVatNumber();
</script>
@endpush
