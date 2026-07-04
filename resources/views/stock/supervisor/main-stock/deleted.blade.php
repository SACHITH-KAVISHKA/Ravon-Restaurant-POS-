@extends('layouts.app')

@section('title', 'Deleted Stock Items')

@push('styles')
    <style>
        .table-container {
            overflow-x: auto;
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
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
                    <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-red-700">
                        Deleted Items</h1>
                    <p class="text-gray-500 text-sm">Soft-deleted stock items. Reactivate an item to make it available
                        again.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('main-stock.index') }}"
                        class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Main Stock
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Filters & Search -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                <form action="{{ route('main-stock.deleted') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by code or name..."
                            class="search-input w-full px-4 py-2 border border-gray-200 rounded-lg focus:border-purple-500">
                    </div>

                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Type</label>
                        <select name="type"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-purple-500">
                            <option value="all">All Types</option>
                            @foreach(\App\Models\MainStockItem::ITEM_TYPES as $value => $label)
                                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                        <a href="{{ route('main-stock.deleted') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Deleted Items Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="table-container">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-red-500 to-red-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                    Item Code</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                    Item Name</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    Type</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    Quantity</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    Unit</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    Price</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    Deleted At</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($items as $item)
                                <tr class="hover:bg-red-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-red-600 font-mono font-semibold">{{ $item->item_code }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-800">{{ $item->item_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        @php
                                            $typeColors = [
                                                'raw_material' => 'bg-blue-100 text-blue-700',
                                                'finished_good' => 'bg-green-100 text-green-700',
                                                'other' => 'bg-gray-100 text-gray-700',
                                            ];
                                        @endphp
                                        <span
                                            class="px-3 py-1 {{ $typeColors[$item->item_type] ?? 'bg-gray-100 text-gray-700' }} rounded-full text-xs font-medium">
                                            {{ $item->item_type_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="font-semibold text-gray-800">{{ number_format($item->quantity, 2) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-600">
                                        {{ $item->unit_abbreviation }}
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        @if($item->price)
                                            <span class="text-gray-800 font-semibold">Rs.
                                                {{ number_format($item->price, 2) }}</span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-600">
                                        {{ $item->updated_at ? $item->updated_at->format('Y-m-d H:i') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <form action="{{ route('main-stock.reactivate', $item) }}" method="POST"
                                            onsubmit="return confirm('Reactivate this item? It will become active again across the system.')"
                                            class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition text-sm font-semibold flex items-center gap-2"
                                                title="Reactivate">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Reactivate
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <h3 class="text-lg font-semibold text-gray-500">No Deleted Items</h3>
                                        <p class="text-gray-400 mt-1">Items deleted from Main Stock will appear here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
