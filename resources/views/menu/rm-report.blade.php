@extends('layouts.app')

@section('title', 'RM Report - Ravon Restaurant POS')

@push('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
@endpush

@section('content')
    <div class="flex h-screen overflow-hidden" x-data="{ 
        editModalOpen: false, 
        recipeId: null, 
        quantity: '', 
        mainStockItemId: '',
        openEditModal(recipe) {
            this.recipeId = recipe.id;
            this.quantity = recipe.quantity;
            this.mainStockItemId = recipe.main_stock_item_id;
            this.editModalOpen = true;
        }
    }">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1
                        class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">
                        Raw Material Report</h1>
                    <p class="text-gray-600">Search for a raw material to see which POS items use it, and update or remove it</p>
                </div>

                @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl shadow-sm text-sm" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
                @endif
                
                @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl shadow-sm text-sm" role="alert">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form action="{{ route('menu.rm-report.index') }}" method="GET" class="space-y-0">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                            <!-- Raw Material Selection -->
                            <div>
                                <label for="raw_material_id" class="block text-sm font-medium text-gray-600 mb-2">
                                    Select Raw Material
                                </label>
                                <select name="raw_material_id" id="raw_material_id" 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    onchange="this.form.submit()">
                                    <option value="">-- Choose a Raw Material --</option>
                                    @foreach($rawMaterials as $rm)
                                    <option value="{{ $rm->id }}" {{ $selectedRmId == $rm->id ? 'selected' : '' }}>
                                        {{ $rm->item_code }} - {{ $rm->item_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex md:justify-end">
                                <a href="{{ route('menu.rm-report.export', request()->query()) }}"
                                    class="w-full md:w-auto bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2.5 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Data Table Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">POS Items Included</h2>
                        <div class="table-responsive">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                        <th class="text-left py-3 px-4 text-white font-semibold rounded-tl-lg">POS Item Name</th>
                                        <th class="text-left py-3 px-4 text-white font-semibold">Portion</th>
                                        <th class="text-center py-3 px-4 text-white font-semibold">Quantity Included</th>
                                        <th class="text-center py-3 px-4 text-white font-semibold rounded-tr-lg">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @if($selectedRmId && $recipes->isEmpty())
                                    <tr>
                                        <td colspan="4" class="text-center py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                                </svg>
                                                <p class="text-gray-600">This raw material is not used in any POS items.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif(!$selectedRmId)
                                    <tr>
                                        <td colspan="4" class="text-center py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                                <p class="text-gray-600">Please select a raw material to view its POS items.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @else
                                        @foreach($recipes as $recipe)
                                        <tr class="hover:bg-purple-50 transition">
                                            <td class="py-3 px-4">
                                                <span class="text-gray-800 font-semibold">{{ $recipe->item->name }}</span>
                                            </td>
                                            <td class="py-3 px-4">
                                                @if($recipe->modifier)
                                                    <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">
                                                        {{ $recipe->modifier->name }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 text-sm">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center py-3 px-4">
                                                <span class="text-purple-600 font-bold">{{ floatval($recipe->quantity) }}</span>
                                            </td>
                                            <td class="text-center py-3 px-4">
                                                <div class="flex items-center justify-center gap-2">
                                                    <!-- Edit Button -->
                                                    <button type="button" @click="openEditModal({{ $recipe->toJson() }})" class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-1.5 px-3 rounded-lg transition duration-200 flex items-center justify-center text-sm">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    
                                                    <!-- Remove Button -->
                                                    <form action="{{ route('menu.rm-report.destroy', $recipe) }}" method="POST" onsubmit="return confirm('Remove this raw material from this POS item?');" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bg-red-600 hover:bg-red-700 shadow-md text-white font-semibold py-1.5 px-3 rounded-lg transition duration-200 flex items-center justify-center text-sm">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal using Tailwind UI/Project similar structure -->
                <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="editModalOpen = false" aria-hidden="true"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div x-show="editModalOpen" x-transition.scale class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-300">
                            <!-- Modal Header -->
                            <div class="bg-gradient-to-r from-[#667eea] to-[#764ba2] px-6 py-4 rounded-t-xl">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-bold text-white flex items-center" id="modal-title">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Update Recipe Row
                                    </h3>
                                    <button type="button" class="text-white hover:text-white/80 transition" @click="editModalOpen = false">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Modal Body -->
                            <form :action="`/menu/rm-report/${recipeId}`" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="px-6 py-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Raw Material</label>
                                            <select name="main_stock_item_id" x-model="mainStockItemId" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                @foreach($rawMaterials as $rm)
                                                    <option value="{{ $rm->id }}">{{ $rm->item_code }} - {{ $rm->item_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                            <input type="number" step="0.001" min="0.001" name="quantity" x-model="quantity" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-xl border-t border-gray-200">
                                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-semibold">
                                        Cancel
                                    </button>
                                    <button type="submit" class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
