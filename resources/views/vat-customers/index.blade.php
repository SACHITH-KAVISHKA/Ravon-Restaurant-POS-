@extends('layouts.app')

@section('title', 'VAT Customer Registration - Ravon POS')

@push('styles')
<style>
    .modal-overlay {
        transition: opacity 0.25s ease;
    }

    .modal-content {
        transition: transform 0.25s ease, opacity 0.25s ease;
    }

    .modal-overlay.hidden .modal-content {
        transform: scale(0.96);
        opacity: 0;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 flex">
    <x-sidebar />

    <div class="flex-1 p-8">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                    VAT Customer Registration
                </h1>
                <p class="text-gray-600 mt-1">Register and maintain VAT customers for billing.</p>
            </div>

            <button onclick="openCreateModal()"
                class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/40 text-white font-semibold px-6 py-2.5 rounded-lg transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New VAT Customer
            </button>
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

        <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white">Name</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white">VAT Number</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white">Telephone</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white">Address</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($customers as $customer)
                        <tr class="hover:bg-purple-50/40 transition">
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $customer->customer_name }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ $customer->vat_number ?: '-' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ $customer->telephone ?: '-' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ $customer->address ?: '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    onclick="openEditModal({{ $customer->id }}, @js($customer->customer_name), @js($customer->vat_number), @js($customer->telephone), @js($customer->address))"
                                    class="inline-flex items-center px-3 py-2 border border-yellow-400 text-yellow-600 rounded-lg hover:bg-yellow-50 transition"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No VAT customers found. Click "Add New VAT Customer" to create one.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="customerModal" class="modal-overlay fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-3xl">
            <div class="bg-gradient-to-r from-[#667eea] to-[#764ba2] p-5 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 id="modalTitle" class="text-xl font-bold text-white">Register New VAT Customer</h3>
                    <button onclick="closeModal()" class="text-white/80 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <form id="customerForm" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" id="formMethod" name="_method" value="POST">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
                    <input type="text" id="customer_name" name="customer_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Enter customer name">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">VAT Number (Optional)</label>
                    <input type="text" id="vat_number" name="vat_number"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="e.g. 103803281-7000">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telephone</label>
                    <input type="text" id="telephone" name="telephone"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Enter telephone number">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea id="address" name="address" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Enter address"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal()"
                        class="px-5 py-2.5 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="submitButton"
                        class="px-5 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/40 transition font-semibold">
                        Register Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const customerModal = document.getElementById('customerModal');
    const customerForm = document.getElementById('customerForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitButton = document.getElementById('submitButton');
    const formMethod = document.getElementById('formMethod');

    function openCreateModal() {
        modalTitle.textContent = 'Register New VAT Customer';
        submitButton.textContent = 'Register Customer';
        customerForm.action = '{{ route('vat-customers.store') }}';
        formMethod.value = 'POST';

        document.getElementById('customer_name').value = '';
        document.getElementById('vat_number').value = '';
        document.getElementById('telephone').value = '';
        document.getElementById('address').value = '';

        customerModal.classList.remove('hidden');
    }

    function openEditModal(id, customerName, vatNumber, telephone, address) {
        modalTitle.textContent = 'Edit VAT Customer';
        submitButton.textContent = 'Update Customer';
        customerForm.action = `/vat-customers/${id}`;
        formMethod.value = 'PUT';

        document.getElementById('customer_name').value = customerName || '';
        document.getElementById('vat_number').value = vatNumber || '';
        document.getElementById('telephone').value = telephone || '';
        document.getElementById('address').value = address || '';

        customerModal.classList.remove('hidden');
    }

    function closeModal() {
        customerModal.classList.add('hidden');
    }

    @if($errors->any())
    openCreateModal();
    @endif
</script>
@endpush
