@extends('layouts.app')

@section('title', 'Add New Menu Item')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('menu.index') }}" class="text-purple-600 hover:text-purple-6002">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent">Add New Menu Item</h1>
                    <p class="text-gray-800-muted mt-1">Create a new item for your menu</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
            <form action="{{ route('menu.items.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <!-- Item Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800-muted mb-2">Item Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500 @error('name') border-red-600 @enderror">
                        @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800-muted mb-2">Category *</label>
                        <select name="category_id" required
                            class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500 @error('category_id') border-red-600 @enderror">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Finished Goods Checkbox -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_finished_goods" value="1" {{ old('is_finished_goods') ? 'checked' : '' }}
                                class="w-5 h-5 text-blue-600 bg-gray-50 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-3 text-gray-800 font-semibold">Finished Goods</span>
                        </label>
                    </div>

                    <!-- Default Price (disabled when has portions) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800-muted mb-2">Default Price (Rs.) *</label>
                        <input type="number" name="price" id="defaultPrice" value="{{ old('price', 0) }}" step="0.01" min="0" required
                            class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
                        <p class="text-gray-800-muted/60 text-sm mt-1">This will be disabled if you enable portions below</p>
                    </div>

                    <!-- Special Prices Section -->
                    <div id="specialPricesSection" class="bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 rounded-lg border border-purple-200">
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-sm font-semibold text-gray-800">Special Prices (Optional)</label>
                            <button type="button" onclick="addSpecialPrice()" class="px-3 py-1.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white rounded-lg transition text-sm font-semibold flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Special Price
                            </button>
                        </div>
                        <div id="specialPricesList" class="space-y-2">
                            <!-- Special price fields will be added here -->
                        </div>
                    </div>

                    <!-- Has Portions Checkbox -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="hasPortions" name="has_portions" value="1" {{ old('has_portions') ? 'checked' : '' }}
                                class="w-5 h-5 text-purple-600 bg-gray-50 border-gray-300 rounded focus:ring-purple-500"
                                onchange="togglePortionFields()">
                            <span class="ml-3 text-gray-800 font-semibold">This item has different portions/sizes</span>
                        </label>
                        <p class="text-gray-800-muted text-sm mt-1 ml-8">Check this if the item comes in different sizes (Small, Large, etc.)</p>
                    </div>

                    <!-- Portions Section (shown when HAS portions) -->
                    <div id="portionsSection" class="{{ old('has_portions') ? '' : 'hidden' }}">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800 mb-3">Add Portions/Sizes</h3>
                            <p class="text-gray-800-muted text-sm mb-4">Add different sizes with their individual prices</p>

                            <div id="portionsList" class="space-y-3">
                                <!-- Portion fields will be added here -->
                            </div>

                            <button type="button" onclick="addPortionField()" class="mt-3 w-full px-4 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold">
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
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold">
                        Create Item
                    </button>
                    <a href="{{ route('menu.index') }}" class="px-6 py-2 bg-gray-50 text-gray-800 rounded-lg hover:bg-white border border-gray-300 transition font-semibold">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let portionCount = 0;
    let specialPriceCount = 0;
    let portionSpecialPriceCounts = {};

    // Special Price Management for Item
    function addSpecialPrice() {
        specialPriceCount++;
        const container = document.getElementById('specialPricesList');

        const priceDiv = document.createElement('div');
        priceDiv.id = `specialPrice-${specialPriceCount}`;
        priceDiv.className = 'flex gap-2 items-center bg-white p-2 rounded border border-purple-200 shadow-sm';

        priceDiv.innerHTML = `
            <div class="flex-1 grid grid-cols-2 gap-2">
                <div>
                    <select name="special_prices[${specialPriceCount}][type]" required
                        class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-purple-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
                        <option value="pickme" selected>Pick Me</option>
                    </select>
                </div>
                <div>
                    <input type="number" name="special_prices[${specialPriceCount}][price]" step="0.01" min="0" required
                        placeholder="Price" class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-purple-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
                </div>
            </div>
            <button type="button" onclick="removeSpecialPrice(${specialPriceCount})" 
                class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;

        container.appendChild(priceDiv);
        updateSpecialPriceInputs();
    }

    function removeSpecialPrice(id) {
        const element = document.getElementById(`specialPrice-${id}`);
        if (element) {
            element.remove();
        }
        updateSpecialPriceInputs();
    }

    function updateSpecialPriceInputs() {
        const container = document.getElementById('specialPricesList');
        const specialPrices = container.querySelectorAll('[id^="specialPrice-"]');

        // Update hidden input for pickme_price based on special prices
        const pickmePrice = Array.from(specialPrices).find(sp => {
            const select = sp.querySelector('select');
            return select && select.value === 'pickme';
        });

        // Create/update hidden input for controller
        let hiddenInput = document.getElementById('pickme_price_hidden');
        if (pickmePrice) {
            const priceInput = pickmePrice.querySelector('input[type="number"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'pickme_price_hidden';
                hiddenInput.name = 'pickme_price';
                document.querySelector('form').appendChild(hiddenInput);
            }
            hiddenInput.value = priceInput.value;
            priceInput.addEventListener('input', function() {
                hiddenInput.value = this.value;
            });
        } else if (hiddenInput) {
            hiddenInput.remove();
        }
    }

    // Special Price Management for Portions
    function addPortionSpecialPrice(portionId) {
        if (!portionSpecialPriceCounts[portionId]) {
            portionSpecialPriceCounts[portionId] = 0;
        }
        portionSpecialPriceCounts[portionId]++;

        const container = document.getElementById(`portionSpecialPrices-${portionId}`);
        const priceId = portionSpecialPriceCounts[portionId];

        const priceDiv = document.createElement('div');
        priceDiv.id = `portionSpecialPrice-${portionId}-${priceId}`;
        priceDiv.className = 'flex gap-2 items-center';

        priceDiv.innerHTML = `
            <select name="portions[${portionId}][special_prices][${priceId}][type]" required
                class="px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
                <option value="pickme" selected>Pick Me</option>
            </select>
            <input type="number" name="portions[${portionId}][special_prices][${priceId}][price]" step="0.01" min="0" required
                placeholder="Price" class="flex-1 px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
            <button type="button" onclick="removePortionSpecialPrice(${portionId}, ${priceId})" 
                class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;

        container.appendChild(priceDiv);
        updatePortionSpecialPriceInputs(portionId);
    }

    function removePortionSpecialPrice(portionId, priceId) {
        const element = document.getElementById(`portionSpecialPrice-${portionId}-${priceId}`);
        if (element) {
            element.remove();
        }
        updatePortionSpecialPriceInputs(portionId);
    }

    function updatePortionSpecialPriceInputs(portionId) {
        const container = document.getElementById(`portionSpecialPrices-${portionId}`);
        const specialPrices = container.querySelectorAll('[id^="portionSpecialPrice-"]');

        // Find pickme price
        const pickmePrice = Array.from(specialPrices).find(sp => {
            const select = sp.querySelector('select');
            return select && select.value === 'pickme';
        });

        // Create/update hidden input for controller
        let hiddenInput = document.getElementById(`portion_pickme_price_hidden_${portionId}`);
        if (pickmePrice) {
            const priceInput = pickmePrice.querySelector('input[type="number"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = `portion_pickme_price_hidden_${portionId}`;
                hiddenInput.name = `portions[${portionId}][pickme_price]`;
                document.querySelector('form').appendChild(hiddenInput);
            }
            hiddenInput.value = priceInput.value;
            priceInput.addEventListener('input', function() {
                hiddenInput.value = this.value;
            });
        } else if (hiddenInput) {
            hiddenInput.remove();
        }
    }

    function togglePortionFields() {
        const hasPortions = document.getElementById('hasPortions').checked;
        const portionsSection = document.getElementById('portionsSection');
        const defaultPriceInput = document.getElementById('defaultPrice');
        const specialPricesSection = document.getElementById('specialPricesSection');

        if (hasPortions) {
            portionsSection.classList.remove('hidden');
            defaultPriceInput.disabled = true;
            defaultPriceInput.classList.add('opacity-50', 'cursor-not-allowed');
            defaultPriceInput.value = 0;
            
            // Disable special prices section when portions are enabled
            if (specialPricesSection) {
                specialPricesSection.classList.add('opacity-50', 'pointer-events-none');
            }

            // Add first portion field if none exist
            if (portionCount === 0) {
                addPortionField();
            }
        } else {
            portionsSection.classList.add('hidden');
            defaultPriceInput.disabled = false;
            defaultPriceInput.classList.remove('opacity-50', 'cursor-not-allowed');
            
            // Re-enable special prices section when portions are disabled
            if (specialPricesSection) {
                specialPricesSection.classList.remove('opacity-50', 'pointer-events-none');
            }

            // Clear portion fields
            document.getElementById('portionsList').innerHTML = '';
            portionCount = 0;
        }
    }

    function addPortionField() {
        // Auto-check the portions checkbox when adding portions
        const hasPortionsCheckbox = document.getElementById('hasPortions');
        if (!hasPortionsCheckbox.checked) {
            hasPortionsCheckbox.checked = true;
            togglePortionFields();
        }
        
        portionCount++;
        const portionsList = document.getElementById('portionsList');

        const portionDiv = document.createElement('div');
        portionDiv.className = 'p-3 bg-white rounded-lg border border-gray-200';
        portionDiv.id = `portion-${portionCount}`;

        portionDiv.innerHTML = `
        <div class="grid grid-cols-2 gap-3 mb-2">
            <div>
                <label class="block text-xs font-semibold text-gray-800-muted mb-1">Portion Name *</label>
                <input type="text" name="portions[${portionCount}][name]" placeholder="e.g., Small, Large" required
                    class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-800-muted mb-1">Price (Rs.) *</label>
                <div class="flex gap-2">
                    <input type="number" name="portions[${portionCount}][price]" step="0.01" min="0" required
                        class="flex-1 px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                    <button type="button" onclick="removePortion(${portionCount})" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-600/90 transition text-sm">
                        ×
                    </button>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-200 pt-2">
            <div class="flex justify-between items-center mb-2">
                <label class="text-xs font-semibold text-gray-800">Special Prices</label>
                <button type="button" onclick="addPortionSpecialPrice(${portionCount})" class="px-2 py-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-md hover:shadow-purple-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add
                </button>
            </div>
            <div id="portionSpecialPrices-${portionCount}" class="space-y-1">
                <!-- Special prices for this portion -->
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
        
        // Check if there are any remaining portions
        const portionsList = document.getElementById('portionsList');
        const remainingPortions = portionsList.querySelectorAll('[id^="portion-"]');
        
        // If no portions left, uncheck the checkbox
        if (remainingPortions.length === 0) {
            const hasPortionsCheckbox = document.getElementById('hasPortions');
            hasPortionsCheckbox.checked = false;
            togglePortionFields();
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        togglePortionFields();
    });
</script>
@endsection