@extends('layouts.app')

@section('title', 'Edit Menu Item')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('menu.index') }}" class="text-purple-600 hover:text-purple-6002">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent">Edit: {{ $item->name }}</h1>
                    <p class="text-gray-800-muted mt-1">Update item details and manage portions</p>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 p-4 bg-green-600/20 border border-ravon-success text-green-600 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Item Details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Item Details</h2>

                    <form action="{{ route('menu.items.update', $item) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <!-- Item Name -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-800-muted mb-2">Item Name *</label>
                                <input type="text" name="name" value="{{ old('name', $item->name) }}" required
                                    class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-800-muted mb-2">Category *</label>
                                <select name="category_id" required
                                    class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $item->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Default Price -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-800-muted mb-2">Default Price (Rs.)</label>
                                <input type="number" name="price" value="{{ old('price', $item->price) }}" step="0.01" min="0"
                                    class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500"
                                    {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                @if($item->modifiers->count() > 0)
                                <p class="text-gray-800-muted/60 text-sm mt-1">Disabled because this item has portions</p>
                                @endif
                            </div>

                            <!-- Special Prices Section -->
                            <div id="specialPricesSection" class="bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 rounded-lg border border-purple-200">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="text-sm font-semibold text-gray-800">Special Prices (Optional)</label>
                                    <button type="button" onclick="addSpecialPrice()" class="px-3 py-1.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white rounded-lg transition text-sm font-semibold flex items-center gap-1"
                                        {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Special Price
                                    </button>
                                </div>
                                <div id="specialPricesList" class="space-y-2">
                                    @php
                                        $existingPrices = $item->itemPrices()->whereNull('item_modifier_id')->get();
                                    @endphp
                                    @foreach($existingPrices as $index => $itemPrice)
                                    <div id="specialPrice-existing-{{ $itemPrice->id }}" class="flex gap-2 items-center bg-white p-2 rounded border border-purple-200 shadow-sm">
                                        <div class="flex-1 grid grid-cols-2 gap-2">
                                            <div>
                                                <select name="special_prices[existing][{{ $itemPrice->id }}][type]" required
                                                    class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-purple-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm"
                                                    {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                                    <option value="pickme" {{ $itemPrice->price_type === 'pickme' ? 'selected' : '' }}>Pick Me</option>
                                                </select>
                                            </div>
                                            <div>
                                                <input type="number" name="special_prices[existing][{{ $itemPrice->id }}][price]" 
                                                    value="{{ $itemPrice->price }}" step="0.01" min="0" required
                                                    placeholder="Price" 
                                                    class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-purple-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm"
                                                    {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                                <input type="hidden" name="special_prices[existing][{{ $itemPrice->id }}][id]" value="{{ $itemPrice->id }}">
                                            </div>
                                        </div>
                                        <button type="button" onclick="removeExistingSpecialPrice({{ $itemPrice->id }})" 
                                            class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm hover:shadow-md"
                                            {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold">
                                Update Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column - Portions -->
            <div>
                <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Portions / Sizes</h2>

                    <!-- Add Portion Form -->
                    <form action="{{ route('menu.modifiers.store', $item) }}" method="POST" class="mb-6">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800-muted mb-1">Portion Name *</label>
                                <input type="text" name="name" placeholder="e.g., Large, Small, 500ml" required
                                    class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800-muted mb-1">Price (Rs.) *</label>
                                <input type="number" name="price" step="0.01" min="0" required
                                    class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                                <p class="text-xs text-gray-800-muted/60 mt-1">Independent price for this portion</p>
                            </div>

                            <div class="bg-gradient-to-br from-purple-50 to-purple-100/50 p-2 rounded border border-purple-200">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="text-xs font-semibold text-gray-800">Special Prices</label>
                                    <button type="button" onclick="addNewPortionSpecialPrice()" class="px-2 py-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-md hover:shadow-purple-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add
                                    </button>
                                </div>
                                <div id="newPortionSpecialPrices" class="space-y-1">
                                    <!-- Special prices for new portion -->
                                </div>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-600/90 text-white rounded-lg transition font-semibold text-sm">
                                Add Portion
                            </button>
                        </div>
                    </form>

                    <!-- Existing Portions List -->
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-800-muted mb-2">Existing Portions</h3>
                        @forelse($item->modifiers as $modifier)
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200" id="portion-{{ $modifier->id }}">
                            <!-- View Mode -->
                            <div class="view-mode-{{ $modifier->id }}">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">{{ $modifier->name }}</h4>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-bold text-green-600">
                                            Rs. {{ number_format($modifier->price_adjustment, 2) }}
                                        </span>
                                        @php
                                            $pickmePrice = $modifier->itemPrices()->where('price_type', 'pickme')->first();
                                        @endphp
                                        @if($pickmePrice)
                                        <div class="text-xs text-blue-600">
                                            Pick Me: Rs. {{ number_format($pickmePrice->price, 2) }}
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-green-600">Active</span>

                                    <div class="flex gap-2">
                                        <button onclick="toggleEditMode({{ $modifier->id }})" class="text-purple-600 hover:text-purple-6002 text-xs">
                                            Edit
                                        </button>
                                        <form action="{{ route('menu.modifiers.destroy', $modifier) }}" method="POST" onsubmit="return confirm('Delete this portion?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-600/80 text-xs">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div class="edit-mode-{{ $modifier->id }} hidden">
                                <form action="{{ route('menu.modifiers.update', $modifier) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="space-y-2">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-800-muted mb-1">Portion Name *</label>
                                            <input type="text" name="name" value="{{ $modifier->name }}" required
                                                class="w-full px-3 py-1.5 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-800-muted mb-1">Price (Rs.) *</label>
                                            <input type="number" name="price" value="{{ $modifier->price_adjustment }}" step="0.01" min="0" required
                                                class="w-full px-3 py-1.5 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                                        </div>

                                        <div class="col-span-2 bg-gradient-to-br from-purple-50 to-purple-100/50 p-2 rounded border border-purple-200">
                                            <div class="flex justify-between items-center mb-2">
                                                <label class="text-xs font-semibold text-gray-800">Special Prices</label>
                                                <button type="button" onclick="addEditPortionSpecialPrice({{ $modifier->id }})" class="px-2 py-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-md hover:shadow-purple-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add
                                                </button>
                                            </div>
                                            <div id="editPortionSpecialPrices-{{ $modifier->id }}" class="space-y-1">
                                                @php
                                                    $modifierPrices = $modifier->itemPrices()->get();
                                                @endphp
                                                @foreach($modifierPrices as $modPrice)
                                                <div id="modifierSpecialPrice-existing-{{ $modPrice->id }}" class="flex gap-2 items-center">
                                                    <select name="modifier_special_prices[existing][{{ $modPrice->id }}][type]" required
                                                        class="px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
                                                        <option value="pickme" {{ $modPrice->price_type === 'pickme' ? 'selected' : '' }}>Pick Me</option>
                                                    </select>
                                                    <input type="number" name="modifier_special_prices[existing][{{ $modPrice->id }}][price]" 
                                                        value="{{ $modPrice->price }}" step="0.01" min="0" required
                                                        placeholder="Price" class="flex-1 px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
                                                    <input type="hidden" name="modifier_special_prices[existing][{{ $modPrice->id }}][id]" value="{{ $modPrice->id }}">
                                                    <input type="hidden" name="modifier_special_prices[existing][{{ $modPrice->id }}][modifier_id]" value="{{ $modifier->id }}">
                                                    <button type="button" onclick="removeExistingModifierSpecialPrice({{ $modPrice->id }})" 
                                                        class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 px-3 py-1.5 bg-green-600 hover:bg-green-600/90 text-white rounded-lg transition text-xs">
                                                Save
                                            </button>
                                            <button type="button" onclick="toggleEditMode({{ $modifier->id }})" class="flex-1 px-3 py-1.5 bg-gray-50 hover:bg-white text-gray-800 rounded-lg transition text-xs border border-gray-300">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-800-muted text-sm text-center py-4">No portions added yet</p>
                        <p class="text-gray-800-muted/60 text-xs text-center">Add portions if this item comes in different sizes</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let specialPriceCount = {{ $item->itemPrices()->whereNull('item_modifier_id')->count() }};
let newPortionSpecialPriceCount = 0;
let editPortionSpecialPriceCounts = {};

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
                <select name="special_prices[new][${specialPriceCount}][type]" required
                    class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-purple-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
                    <option value="pickme" selected>Pick Me</option>
                </select>
            </div>
            <div>
                <input type="number" name="special_prices[new][${specialPriceCount}][price]" step="0.01" min="0" required
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
    updateItemSpecialPriceInputs();
}

function removeSpecialPrice(id) {
    const element = document.getElementById(`specialPrice-${id}`);
    if (element) {
        element.remove();
    }
    updateItemSpecialPriceInputs();
}

function removeExistingSpecialPrice(id) {
    const element = document.getElementById(`specialPrice-existing-${id}`);
    if (element) {
        // Add hidden input to mark for deletion
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = `special_prices[delete][]`;
        deleteInput.value = id;
        document.querySelector('form').appendChild(deleteInput);
        element.remove();
    }
    updateItemSpecialPriceInputs();
}

function updateItemSpecialPriceInputs() {
    const container = document.getElementById('specialPricesList');
    const allPrices = container.querySelectorAll('[id^="specialPrice-"]');
    
    // Find pickme price (existing or new)
    const pickmePrice = Array.from(allPrices).find(sp => {
        const select = sp.querySelector('select');
        return select && select.value === 'pickme';
    });
    
    // Update hidden input for backward compatibility
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

// Special Price Management for New Portion
function addNewPortionSpecialPrice() {
    newPortionSpecialPriceCount++;
    const container = document.getElementById('newPortionSpecialPrices');
    
    const priceDiv = document.createElement('div');
    priceDiv.id = `newPortionSpecialPrice-${newPortionSpecialPriceCount}`;
    priceDiv.className = 'flex gap-1 items-center';
    
    priceDiv.innerHTML = `
        <select name="portion_special_prices[${newPortionSpecialPriceCount}][type]" required
            class="px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
            <option value="pickme" selected>Pick Me</option>
        </select>
        <input type="number" name="portion_special_prices[${newPortionSpecialPriceCount}][price]" step="0.01" min="0" required
            placeholder="Price" class="flex-1 px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
        <button type="button" onclick="removeNewPortionSpecialPrice(${newPortionSpecialPriceCount})" 
            class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;
    
    container.appendChild(priceDiv);
    updateNewPortionSpecialPrices();
}

function removeNewPortionSpecialPrice(id) {
    const element = document.getElementById(`newPortionSpecialPrice-${id}`);
    if (element) {
        element.remove();
    }
    updateNewPortionSpecialPrices();
}

function updateNewPortionSpecialPrices() {
    const container = document.getElementById('newPortionSpecialPrices');
    const specialPrices = container.querySelectorAll('[id^="newPortionSpecialPrice-"]');
    
    const pickmePrice = Array.from(specialPrices).find(sp => {
        const select = sp.querySelector('select');
        return select && select.value === 'pickme';
    });
    
    let hiddenInput = document.getElementById('new_portion_pickme_price_hidden');
    if (pickmePrice) {
        const priceInput = pickmePrice.querySelector('input[type="number"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = 'new_portion_pickme_price_hidden';
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

// Special Price Management for Edit Portion
function addEditPortionSpecialPrice(modifierId) {
    if (!editPortionSpecialPriceCounts[modifierId]) {
        editPortionSpecialPriceCounts[modifierId] = 0;
    }
    editPortionSpecialPriceCounts[modifierId]++;
    
    const container = document.getElementById(`editPortionSpecialPrices-${modifierId}`);
    const priceId = editPortionSpecialPriceCounts[modifierId];
    
    const priceDiv = document.createElement('div');
    priceDiv.id = `modifierSpecialPrice-${modifierId}-${priceId}`;
    priceDiv.className = 'flex gap-1 items-center';
    
    priceDiv.innerHTML = `
        <select name="modifier_special_prices[new][${modifierId}][${priceId}][type]" required
            class="px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
            <option value="pickme" selected>Pick Me</option>
        </select>
        <input type="number" name="modifier_special_prices[new][${modifierId}][${priceId}][price]" step="0.01" min="0" required
            placeholder="Price" class="flex-1 px-3 py-2 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm">
        <input type="hidden" name="modifier_special_prices[new][${modifierId}][${priceId}][modifier_id]" value="${modifierId}">
        <button type="button" onclick="removeEditPortionSpecialPrice(${modifierId}, ${priceId})" 
            class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;
    
    container.appendChild(priceDiv);
    updateEditPortionSpecialPrices(modifierId);
}

function removeEditPortionSpecialPrice(modifierId, priceId) {
    const element = document.getElementById(`modifierSpecialPrice-${modifierId}-${priceId}`);
    if (element) {
        element.remove();
    }
    updateEditPortionSpecialPrices(modifierId);
}

function removeExistingModifierSpecialPrice(id) {
    const element = document.getElementById(`modifierSpecialPrice-existing-${id}`);
    if (element) {
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = `modifier_special_prices[delete][]`;
        deleteInput.value = id;
        document.querySelector('form').appendChild(deleteInput);
        element.remove();
    }
}

function updateEditPortionSpecialPrices(modifierId) {
    const container = document.getElementById(`editPortionSpecialPrices-${modifierId}`);
    const specialPrices = container.querySelectorAll('[id^="modifierSpecialPrice-"]');
    
    const pickmePrice = Array.from(specialPrices).find(sp => {
        const select = sp.querySelector('select');
        return select && select.value === 'pickme';
    });
    
    let hiddenInput = document.getElementById(`edit_portion_pickme_price_hidden_${modifierId}`);
    if (pickmePrice) {
        const priceInput = pickmePrice.querySelector('input[type="number"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = `edit_portion_pickme_price_hidden_${modifierId}`;
            hiddenInput.name = 'pickme_price';
            const form = pickmePrice.closest('form');
            if (form) form.appendChild(hiddenInput);
        }
        if (hiddenInput) {
            hiddenInput.value = priceInput.value;
            priceInput.addEventListener('input', function() {
                hiddenInput.value = this.value;
            });
        }
    } else if (hiddenInput) {
        hiddenInput.remove();
    }
}

function toggleEditMode(modifierId) {
    const viewMode = document.querySelector('.view-mode-' + modifierId);
    const editMode = document.querySelector('.edit-mode-' + modifierId);
    
    if (viewMode.classList.contains('hidden')) {
        viewMode.classList.remove('hidden');
        editMode.classList.add('hidden');
    } else {
        viewMode.classList.add('hidden');
        editMode.classList.remove('hidden');
    }
}
</script>
@endsection


