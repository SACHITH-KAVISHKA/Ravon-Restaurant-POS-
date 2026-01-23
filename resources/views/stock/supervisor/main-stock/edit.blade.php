@extends('layouts.app')

@section('title', 'Edit Stock Item')

@push('styles')
<style>
    .form-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        border-color: #667eea;
    }

    .form-select:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        border-color: #667eea;
    }

    .toggle-checkbox:checked {
        background-color: #667eea;
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
                <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#667eea] to-[#764ba2]">Edit Stock Item</h1>
                <p class="text-gray-500 text-sm">Update item details for <span class="font-semibold text-purple-600">{{ $item->item_code }}</span></p>
            </div>
            <a href="{{ route('main-stock.index') }}" class="text-gray-600 hover:text-gray-800 font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>

        @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden max-w-3xl">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Item Details</h2>
                        <p class="text-sm text-gray-500">Update the information below</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Current Stock</p>
                        <p class="text-xl font-bold {{ $item->isLowStock() ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($item->quantity, 2) }} {{ $item->unit_abbreviation }}
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('main-stock.update', $item) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Item Code -->
                    <div>
                        <label for="item_code" class="block text-sm font-medium text-gray-700 mb-1">
                            Item Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="item_code" id="item_code" value="{{ old('item_code', $item->item_code) }}"
                            class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg font-mono" required>
                        @error('item_code')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Item Name -->
                    <div>
                        <label for="item_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Item Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="item_name" id="item_name" value="{{ old('item_name', $item->item_name) }}"
                            class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg" required>
                        @error('item_name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Item Type -->
                    <div>
                        <label for="item_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Item Type <span class="text-red-500">*</span>
                        </label>
                        <select name="item_type" id="item_type" class="form-select w-full px-4 py-2.5 border border-gray-200 rounded-lg" required onchange="handleItemTypeChange()">
                            @foreach(\App\Models\MainStockItem::ITEM_TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('item_type', $item->item_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('item_type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit Type -->
                    <div>
                        <label for="unit_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Unit of Measurement <span class="text-red-500">*</span>
                        </label>
                        <select name="unit_type" id="unit_type" class="form-select w-full px-4 py-2.5 border border-gray-200 rounded-lg" required>
                            @foreach(\App\Models\MainStockItem::UNIT_TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('unit_type', $item->unit_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('unit_type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Normalization (only for Raw Materials) -->
                    <div id="normalization-field" class="{{ old('item_type', $item->item_type) === 'raw_material' ? '' : 'hidden' }}">
                        <label for="normalization" class="block text-sm font-medium text-gray-700 mb-1">
                            Normalization Factor
                        </label>
                        <input type="number" name="normalization" id="normalization" 
                            value="{{ old('normalization', $item->normalization) }}"
                            class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg" 
                            step="0.0001" min="0" placeholder="Optional - e.g., 1.5">
                        <p class="mt-1 text-xs text-gray-500">If set, displayed quantity = actual quantity × normalization</p>
                        @error('normalization')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <!-- Linked Menu Item Row (Only for Finished Goods) -->
                <div id="finished-goods-row" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="linked_item_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Select Menu Item
                        </label>
                        <select name="linked_item_id" id="linked_item_id" class="form-select w-full px-4 py-2.5 border border-gray-200 rounded-lg" onchange="onLinkedItemChange()">
                            <option value="">-- Select Beverage/Dessert --</option>
                            @foreach($finishedGoodsItems as $fgItem)
                            @if($fgItem->activeModifiers->count() > 0)
                            {{-- Item has portions - show each portion --}}
                            @foreach($fgItem->activeModifiers as $portion)
                            <option value="{{ $fgItem->id }}_{{ $portion->id }}"
                                data-name="{{ $fgItem->name }} - {{ $portion->name }}"
                                data-item-id="{{ $fgItem->id }}"
                                data-portion-id="{{ $portion->id }}"
                                data-category="{{ $fgItem->category->name ?? '' }}"
                                {{ old('linked_item_id') == $fgItem->id.'_'.$portion->id ? 'selected' : '' }}>
                                [{{ $fgItem->category->name ?? 'N/A' }}] {{ $fgItem->name }} - {{ $portion->name }}
                            </option>
                            @endforeach
                            @else
                            {{-- Item has no portions - show item name only --}}
                            <option value="{{ $fgItem->id }}"
                                data-name="{{ $fgItem->name }}"
                                data-item-id="{{ $fgItem->id }}"
                                data-category="{{ $fgItem->category->name ?? '' }}"
                                {{ old('linked_item_id') == $fgItem->id ? 'selected' : '' }}>
                                [{{ $fgItem->category->name ?? 'N/A' }}] {{ $fgItem->name }}
                            </option>
                            @endif
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select a menu item to auto-fill the item name</p>
                        @error('linked_item_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                        {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-gray-700">
                        Active Item
                        <span class="block text-xs text-gray-500">Inactive items won't appear in stock operations</span>
                    </label>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('main-stock.index') }}" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold rounded-lg transition">
                        Update Stock Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle item type change - show/hide finished goods dropdown and normalization field
    function handleItemTypeChange() {
        const itemType = document.getElementById('item_type').value;
        const finishedGoodsRow = document.getElementById('finished-goods-row');
        const normalizationField = document.getElementById('normalization-field');
        const normalizationInput = document.getElementById('normalization');

        if (itemType === 'finished_good') {
            finishedGoodsRow.classList.remove('hidden');
            normalizationField.classList.add('hidden');
            if (normalizationInput) {
                normalizationInput.value = '';
            }
        } else if (itemType === 'raw_material') {
            finishedGoodsRow.classList.add('hidden');
            document.getElementById('linked_item_id').value = '';
            normalizationField.classList.remove('hidden');
        } else {
            finishedGoodsRow.classList.add('hidden');
            document.getElementById('linked_item_id').value = '';
            normalizationField.classList.add('hidden');
            if (normalizationInput) {
                normalizationInput.value = '';
            }
        }
    }

    // When a linked item is selected, autofill the item name
    function onLinkedItemChange() {
        const linkedItemSelect = document.getElementById('linked_item_id');
        const itemNameInput = document.getElementById('item_name');

        if (linkedItemSelect.value) {
            const selectedOption = linkedItemSelect.options[linkedItemSelect.selectedIndex];
            const itemName = selectedOption.getAttribute('data-name');
            if (itemName) {
                itemNameInput.value = itemName;
            }
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        handleItemTypeChange();
    });
</script>
@endsection