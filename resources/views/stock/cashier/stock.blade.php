@extends('layouts.app')

@section('title', 'My Stock - Cashier')

@push('styles')
<style>
    .stock-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .stock-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem 1.5rem;
        text-align: left;
    }

    .stock-table th:first-child {
        border-radius: 0.75rem 0 0 0;
    }

    .stock-table th:last-child {
        border-radius: 0 0.75rem 0 0;
    }

    .stock-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .stock-table tbody tr {
        transition: all 0.2s ease;
    }

    .stock-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .stock-table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-low {
        background-color: #fef2f2;
        color: #dc2626;
    }

    .status-ok {
        background-color: #f0fdf4;
        color: #16a34a;
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
                <h1 class="text-2xl font-bold text-gray-800">Stock</h1>
                <p class="text-gray-500 text-sm">View available stock at your restaurant</p>
            </div>
        </div>

        <!-- Stock Table Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            @if($stocks->isEmpty())
            <div class="text-center py-20">
                <svg class="w-24 h-24 mx-auto text-gray-300 empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-600">No Stock Available</h3>
                <p class="text-gray-400 mt-1">Request stock from supervisor to get started</p>
                <a href="{{ route('stock.cashier.index') }}" class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-purple-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Stock Request
                </a>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Item Name</th>
                            <th>Size/Portion</th>
                            <th>Category</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stocks as $index => $stock)
                        <tr>
                            <td class="text-gray-400 font-medium">{{ $index + 1 }}</td>
                            <td>
                                <div class="font-semibold text-gray-800">{{ $stock->item->name }}</div>
                            </td>
                            <td>
                                @if($stock->itemModifier)
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
                                    {{ $stock->itemModifier->name }}
                                </span>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-gray-600">{{ $stock->item->category->name ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="text-2xl font-bold {{ $stock->quantity <= 10 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($stock->quantity, 0) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="text-gray-500">{{ $stock->unit }}</span>
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
@endsection