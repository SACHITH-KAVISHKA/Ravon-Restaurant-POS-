@extends('layouts.app')

@section('title', 'Add New Menu Item')

@section('content')
<div class="min-h-screen bg-gray-900 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('menu.index') }}" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-white">Add New Menu Item</h1>
                    <p class="text-gray-400 mt-1">Create a new item for your menu</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-gray-800 rounded-lg shadow-lg p-6">
            <form action="{{ route('menu.items.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <!-- Item Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Item Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500 @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Category *</label>
                        <select name="category_id" required
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500 @error('category_id') border-red-500 @enderror">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Default Price (disabled when has portions) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Default Price (Rs.) *</label>
                        <input type="number" name="price" id="defaultPrice" value="{{ old('price', 0) }}" step="0.01" min="0" required
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                        <p class="text-gray-500 text-sm mt-1">This will be disabled if you add different portions</p>
                    </div>

                    <!-- Has Portions Checkbox -->
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="hasPortions" name="has_portions" value="1" {{ old('has_portions') ? 'checked' : '' }}
                                class="w-5 h-5 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500"
                                onchange="togglePortionFields()">
                            <span class="ml-3 text-white font-semibold">This item has different portions/sizes</span>
                        </label>
                        <p class="text-gray-400 text-sm mt-1 ml-8">Check this if the item comes in different sizes (Small, Large, etc.)</p>
                    </div>

                    <!-- Portions Section (shown when HAS portions) -->
                    <div id="portionsSection" class="{{ old('has_portions') ? '' : 'hidden' }}">
                        <div class="bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-bold text-white mb-3">Add Portions/Sizes</h3>
                            <p class="text-gray-400 text-sm mb-4">Add different sizes with their individual prices</p>

                            <div id="portionsList" class="space-y-3">
                                <!-- Portion fields will be added here -->
                            </div>

                            <button type="button" onclick="addPortionField()" class="mt-3 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                + Add Portion
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields with default values -->
                <input type="hidden" name="is_available" value="1">
                <input type="hidden" name="is_featured" value="0">
                <input type="hidden" name="display_order" value="0">

                <!-- Submit Buttons -->
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        Create Item
                    </button>
                    <a href="{{ route('menu.index') }}" class="px-6 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition font-semibold">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let portionCount = 0;

    function togglePortionFields() {
        const hasPortions = document.getElementById('hasPortions').checked;
        const portionsSection = document.getElementById('portionsSection');
        const defaultPriceInput = document.getElementById('defaultPrice');

        if (hasPortions) {
            portionsSection.classList.remove('hidden');
            defaultPriceInput.disabled = true;
            defaultPriceInput.classList.add('opacity-50', 'cursor-not-allowed');
            defaultPriceInput.value = 0;

            // Add first portion field if none exist
            if (portionCount === 0) {
                addPortionField();
            }
        } else {
            portionsSection.classList.add('hidden');
            defaultPriceInput.disabled = false;
            defaultPriceInput.classList.remove('opacity-50', 'cursor-not-allowed');

            // Clear portion fields
            document.getElementById('portionsList').innerHTML = '';
            portionCount = 0;
        }
    }

    function addPortionField() {
        portionCount++;
        const portionsList = document.getElementById('portionsList');

        const portionDiv = document.createElement('div');
        portionDiv.className = 'grid grid-cols-2 gap-3 p-3 bg-gray-800 rounded-lg';
        portionDiv.id = `portion-${portionCount}`;

        portionDiv.innerHTML = `
        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-1">Portion Name *</label>
            <input type="text" name="portions[${portionCount}][name]" placeholder="e.g., Small, Large" required
                class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-1">Price (Rs.) *</label>
            <div class="flex gap-2">
                <input type="number" name="portions[${portionCount}][price]" step="0.01" min="0" required
                    class="flex-1 px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500 text-sm">
                <button type="button" onclick="removePortion(${portionCount})" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                    ×
                </button>
            </div>
        </div>
    `;

        portionsList.appendChild(portionDiv);
    }

    function removePortion(id) {
        const portionDiv = document.getElementById(`portion-${id}`);
        if (portionDiv) {
            portionDiv.remove();
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        togglePortionFields();
    });
</script>
@endsection