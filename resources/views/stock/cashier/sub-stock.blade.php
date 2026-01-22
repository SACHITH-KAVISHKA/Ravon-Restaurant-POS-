@extends('layouts.app')

@section('title', ($type === 'raw_material' ? 'Raw Material Stock' : 'Finished Goods Stock') . ' - Cashier')

@push('styles')
    <style>
        .stock-card {
            transition: all 0.3s ease;
        }

        .stock-card:hover {
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

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    <h1 class="text-2xl font-bold text-gray-800">
                        @if($type === 'raw_material')
                            Raw Material Stock
                        @else
                            Finished Goods Stock
                        @endif
                    </h1>
                    <p class="text-gray-500 text-sm">
                        @if($type === 'raw_material')
                            Raw materials in your inventory
                        @else
                            Finished goods in your inventory
                        @endif
                    </p>
                </div>
            </div>

            <!-- Stock List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 {{ $type === 'raw_material' ? 'bg-gradient-to-r from-blue-600 to-blue-700' : 'bg-gradient-to-r from-emerald-600 to-emerald-700' }}">
                    <h2 class="font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        @if($type === 'raw_material')
                            Raw Material Inventory
                        @else
                            Finished Goods Inventory
                        @endif
                    </h2>
                </div>

                @if($subStock->isEmpty())
                    <div class="text-center py-16">
                        <svg class="w-24 h-24 mx-auto text-gray-200 empty-state-icon" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-500">No Stock Items Yet</h3>
                        <p class="text-gray-400 mt-1">Accept incoming transfers to add items to your inventory</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Item Code</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Item Name</th>
                                    <th
                                        class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Quantity</th>
                                    <th
                                        class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Unit</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Last Updated</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($subStock as $stock)
                                    <tr
                                        class="hover:bg-{{ $type === 'raw_material' ? 'blue' : 'emerald' }}-50 transition-colors stock-card">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ $stock->mainStockItem->item_code }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-semibold text-gray-800">{{ $stock->mainStockItem->item_name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <span
                                                class="text-xl font-bold {{ $type === 'raw_material' ? 'text-blue-600' : 'text-emerald-600' }}">{{ $type === 'raw_material' ? number_format($stock->quantity, 3) : number_format($stock->quantity, 0) }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            <span
                                                class="text-gray-500 font-medium">{{ $stock->mainStockItem->unit_abbreviation }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm">
                                            {{ $stock->updated_at->format('M d, Y h:i A') }}
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