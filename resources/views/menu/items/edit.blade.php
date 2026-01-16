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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1
                            class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent">
                            Edit: {{ $item->name }}</h1>
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

                                <!-- Finished Goods & Stock Count Checkboxes -->
                                <div class="flex gap-4">
                                    <div class="flex-1 bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" name="is_finished_goods" id="isFinishedGoodsEdit"
                                                value="1" {{ old('is_finished_goods', $item->is_finished_goods) ? 'checked' : '' }}
                                                class="w-5 h-5 text-blue-600 bg-gray-50 border-gray-300 rounded focus:ring-blue-500"
                                                onchange="toggleFinishedGoodsRecipeEdit()">
                                            <span class="ml-3 text-gray-800 font-semibold">Finished Goods</span>
                                        </label>
                                    </div>

                                    <div class="flex-1 bg-green-50 p-4 rounded-lg border border-green-200">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" name="is_stock_count" value="1" {{ old('is_stock_count', $item->is_stock_count ?? true) ? 'checked' : '' }}
                                                class="w-5 h-5 text-green-600 bg-gray-50 border-gray-300 rounded focus:ring-green-500">
                                            <span class="ml-3 text-gray-800 font-semibold">Stock Count</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Enable Portions Checkbox -->
                                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" id="hasPortionsEdit" {{ $item->modifiers->count() > 0 ? 'checked' : '' }}
                                            class="w-5 h-5 text-purple-600 bg-gray-50 border-gray-300 rounded focus:ring-purple-500"
                                            onchange="togglePortionSectionEdit()">
                                        <span class="ml-3 text-gray-800 font-semibold">This item has different
                                            portions/sizes</span>
                                    </label>
                                </div>

                                <!-- Default Price -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800-muted mb-2">Default Price
                                        (Rs.)</label>
                                    <input type="number" name="price" value="{{ old('price', $item->price) }}" step="0.01"
                                        min="0"
                                        class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500"
                                        {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                    @if($item->modifiers->count() > 0)
                                        <p class="text-gray-800-muted/60 text-sm mt-1">Disabled because this item has portions
                                        </p>
                                    @endif
                                </div>

                                <!-- Special Prices Section -->
                                <div id="specialPricesSection"
                                    class="bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 rounded-lg border border-purple-200">
                                    <div class="flex justify-between items-center mb-3">
                                        <label class="text-sm font-semibold text-gray-800">Special Prices</label>
                                        <button type="button" onclick="addSpecialPrice()"
                                            class="px-3 py-1.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white rounded-lg transition text-sm font-semibold flex items-center gap-1"
                                            {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add Special Price
                                        </button>
                                    </div>
                                    <div id="specialPricesList" class="space-y-2">
                                        @php
                                            $existingPrices = $item->itemPrices()->whereNull('item_modifier_id')->get();
                                        @endphp
                                        @foreach($existingPrices as $index => $itemPrice)
                                            <div id="specialPrice-existing-{{ $itemPrice->id }}"
                                                class="flex gap-2 items-center bg-white p-2 rounded border border-purple-200 shadow-sm">
                                                <div class="flex-1 grid grid-cols-2 gap-2">
                                                    <div>
                                                        <select name="special_prices[existing][{{ $itemPrice->id }}][type]"
                                                            required
                                                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-purple-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm"
                                                            {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                                            <option value="pickme" {{ $itemPrice->price_type === 'pickme' ? 'selected' : '' }}>Pick Me</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <input type="number"
                                                            name="special_prices[existing][{{ $itemPrice->id }}][price]"
                                                            value="{{ $itemPrice->price }}" step="0.01" min="0" required
                                                            placeholder="Price"
                                                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-purple-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm"
                                                            {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                                        <input type="hidden"
                                                            name="special_prices[existing][{{ $itemPrice->id }}][id]"
                                                            value="{{ $itemPrice->id }}">
                                                    </div>
                                                </div>
                                                <button type="button" onclick="removeExistingSpecialPrice({{ $itemPrice->id }})"
                                                    class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm hover:shadow-md"
                                                    {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Recipe Section (for items without portions) -->
                                <div id="recipeSection"
                                    class="bg-gradient-to-br from-amber-50 to-orange-100/50 p-4 rounded-lg border border-amber-200 {{ $item->modifiers->count() > 0 ? 'opacity-50 pointer-events-none' : '' }}">
                                    <div class="flex justify-between items-center mb-3">
                                        <label class="text-sm font-semibold text-gray-800">Recipe (Raw Materials)</label>
                                        <button type="button" onclick="addRecipeRow()"
                                            class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:shadow-lg hover:shadow-amber-500/50 text-white rounded-lg transition text-sm font-semibold flex items-center gap-1"
                                            {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add Recipe
                                        </button>
                                    </div>
                                    <div id="recipesList" class="space-y-2">
                                        @foreach($item->itemRecipes as $recipe)
                                            <div id="recipe-existing-{{ $recipe->id }}"
                                                class="flex gap-2 items-center bg-white p-2 rounded border border-amber-200 shadow-sm">
                                                <div class="flex-1 grid grid-cols-3 gap-2">
                                                    <div class="col-span-1">
                                                        <select name="recipes[existing][{{ $recipe->id }}][main_stock_item_id]"
                                                            required
                                                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-amber-300 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 text-sm">
                                                            @foreach($rawMaterials as $material)
                                                                <option value="{{ $material->id }}"
                                                                    data-unit="{{ $material->unit_abbreviation }}" {{ $recipe->main_stock_item_id == $material->id ? 'selected' : '' }}>
                                                                    {{ $material->item_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" name="recipes[existing][{{ $recipe->id }}][id]"
                                                            value="{{ $recipe->id }}">
                                                    </div>
                                                    <div>
                                                        <input type="number"
                                                            name="recipes[existing][{{ $recipe->id }}][quantity]"
                                                            value="{{ $recipe->quantity }}" step="0.001" min="0.001" required
                                                            placeholder="Quantity"
                                                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-amber-300 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 text-sm">
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span
                                                            class="text-sm text-gray-600 font-medium px-2">{{ $recipe->mainStockItem->unit_abbreviation ?? '--' }}</span>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="removeExistingRecipe({{ $recipe->id }})"
                                                    class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm hover:shadow-md">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-6">
                                <button type="submit"
                                    class="px-6 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold">
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

                        <!-- Portions Content (shown when checkbox is checked) -->
                        <div id="portionsSectionEdit" class="{{ $item->modifiers->count() > 0 ? '' : 'hidden' }}">
                            <!-- Add Portion Form -->
                            <form action="{{ route('menu.modifiers.store', $item) }}" method="POST" class="mb-6">
                                @csrf
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800-muted mb-1">Portion Name
                                            *</label>
                                        <input type="text" name="name" placeholder="e.g., Large, Small, 500ml" required
                                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800-muted mb-1">Price (Rs.)
                                            *</label>
                                        <input type="number" name="price" step="0.01" min="0" required
                                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                                        <p class="text-xs text-gray-800-muted/60 mt-1">Independent price for this portion
                                        </p>
                                    </div>

                                    <div
                                        class="bg-gradient-to-br from-purple-50 to-purple-100/50 p-2 rounded border border-purple-200">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="text-xs font-semibold text-gray-800">Special Prices</label>
                                            <button type="button" onclick="addNewPortionSpecialPrice()"
                                                class="px-2 py-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-md hover:shadow-purple-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v16m8-8H4" />
                                                </svg>
                                                Add
                                            </button>
                                        </div>
                                        <div id="newPortionSpecialPrices" class="space-y-1">
                                            <!-- Special prices for new portion -->
                                        </div>
                                    </div>

                                    <div
                                        class="bg-gradient-to-br from-amber-50 to-orange-100/50 p-2 rounded border border-amber-200">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="text-xs font-semibold text-amber-700">Recipe (Raw
                                                Materials)</label>
                                            <button type="button" onclick="addNewPortionRecipe()"
                                                class="px-2 py-1 bg-gradient-to-r from-amber-500 to-orange-500 hover:shadow-md hover:shadow-amber-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v16m8-8H4" />
                                                </svg>
                                                Add
                                            </button>
                                        </div>
                                        <div id="newPortionRecipes" class="space-y-1">
                                            <!-- Recipes for new portion -->
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="w-full px-4 py-2 bg-green-600 hover:bg-green-600/90 text-white rounded-lg transition font-semibold text-sm">
                                        Add Portion
                                    </button>
                                </div>
                            </form>

                            <!-- Existing Portions List -->
                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold text-gray-800-muted mb-2">Existing Portions</h3>
                                @forelse($item->modifiers as $modifier)
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200"
                                        id="portion-{{ $modifier->id }}">
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

                                                <div class="flex gap-3">
                                                    <button onclick="toggleEditMode({{ $modifier->id }})"
                                                        class="flex items-center gap-1 text-purple-600 hover:text-purple-800 text-xs font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                    <form action="{{ route('menu.modifiers.destroy', $modifier) }}"
                                                        method="POST" onsubmit="return confirm('Delete this portion?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="flex items-center gap-1 text-red-600 hover:text-red-800 text-xs font-medium">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
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
                                                        <label
                                                            class="block text-xs font-semibold text-gray-800-muted mb-1">Portion
                                                            Name *</label>
                                                        <input type="text" name="name" value="{{ $modifier->name }}" required
                                                            class="w-full px-3 py-1.5 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                                                    </div>

                                                    <div>
                                                        <label
                                                            class="block text-xs font-semibold text-gray-800-muted mb-1">Price
                                                            (Rs.) *</label>
                                                        <input type="number" name="price"
                                                            value="{{ $modifier->price_adjustment }}" step="0.01" min="0"
                                                            required
                                                            class="w-full px-3 py-1.5 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 text-sm">
                                                    </div>

                                                    <div
                                                        class="col-span-2 bg-gradient-to-br from-purple-50 to-purple-100/50 p-2 rounded border border-purple-200">
                                                        <div class="flex justify-between items-center mb-2">
                                                            <label class="text-xs font-semibold text-gray-800">Special
                                                                Prices</label>
                                                            <button type="button"
                                                                onclick="addEditPortionSpecialPrice({{ $modifier->id }})"
                                                                class="px-2 py-1 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-md hover:shadow-purple-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                                Add
                                                            </button>
                                                        </div>
                                                        <div id="editPortionSpecialPrices-{{ $modifier->id }}"
                                                            class="space-y-1">
                                                            @php
                                                                $modifierPrices = $modifier->itemPrices()->get();
                                                            @endphp
                                                            @foreach($modifierPrices as $modPrice)
                                                                <div id="modifierSpecialPrice-existing-{{ $modPrice->id }}"
                                                                    class="flex gap-1 items-center">
                                                                    <select
                                                                        name="modifier_special_prices[existing][{{ $modPrice->id }}][type]"
                                                                        required
                                                                        class="px-2 py-1.5 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 text-xs">
                                                                        <option value="pickme" {{ $modPrice->price_type === 'pickme' ? 'selected' : '' }}>Pick Me</option>
                                                                    </select>
                                                                    <input type="number"
                                                                        name="modifier_special_prices[existing][{{ $modPrice->id }}][price]"
                                                                        value="{{ $modPrice->price }}" step="0.01" min="0" required
                                                                        placeholder="Price"
                                                                        class="w-20 px-2 py-1.5 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 text-xs">
                                                                    <input type="hidden"
                                                                        name="modifier_special_prices[existing][{{ $modPrice->id }}][id]"
                                                                        value="{{ $modPrice->id }}">
                                                                    <input type="hidden"
                                                                        name="modifier_special_prices[existing][{{ $modPrice->id }}][modifier_id]"
                                                                        value="{{ $modifier->id }}">
                                                                    <button type="button"
                                                                        onclick="removeExistingModifierSpecialPrice({{ $modPrice->id }})"
                                                                        class="px-1.5 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                            viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="col-span-2 bg-gradient-to-br from-amber-50 to-orange-100/50 p-2 rounded border border-amber-200">
                                                        <div class="flex justify-between items-center mb-2">
                                                            <label class="text-xs font-semibold text-amber-700">Recipe (Raw
                                                                Materials)</label>
                                                            <button type="button"
                                                                onclick="addEditPortionRecipe({{ $modifier->id }})"
                                                                class="px-2 py-1 bg-gradient-to-r from-amber-500 to-orange-500 hover:shadow-md hover:shadow-amber-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                                Add
                                                            </button>
                                                        </div>
                                                        <div id="editPortionRecipes-{{ $modifier->id }}" class="space-y-1">
                                                            @foreach($modifier->recipes as $recipe)
                                                                <div id="modifierRecipe-existing-{{ $recipe->id }}"
                                                                    class="flex gap-1 items-center">
                                                                    <select
                                                                        name="modifier_recipes[existing][{{ $recipe->id }}][main_stock_item_id]"
                                                                        required
                                                                        class="flex-1 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                                                                        @foreach($rawMaterials as $material)
                                                                            <option value="{{ $material->id }}"
                                                                                data-unit="{{ $material->unit_abbreviation }}" {{ $recipe->main_stock_item_id == $material->id ? 'selected' : '' }}>
                                                                                {{ $material->item_name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <input type="number"
                                                                        name="modifier_recipes[existing][{{ $recipe->id }}][quantity]"
                                                                        value="{{ $recipe->quantity }}" step="0.001" min="0.001"
                                                                        required placeholder="Qty"
                                                                        class="w-16 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                                                                    <span
                                                                        class="text-xs text-gray-600 w-8">{{ $recipe->mainStockItem->unit_abbreviation ?? '--' }}</span>
                                                                    <input type="hidden"
                                                                        name="modifier_recipes[existing][{{ $recipe->id }}][id]"
                                                                        value="{{ $recipe->id }}">
                                                                    <button type="button"
                                                                        onclick="removeExistingModifierRecipe({{ $recipe->id }})"
                                                                        class="px-1.5 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                            viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <div class="flex gap-2">
                                                        <button type="submit"
                                                            class="flex-1 px-3 py-1.5 bg-green-600 hover:bg-green-600/90 text-white rounded-lg transition text-xs">
                                                            Save
                                                        </button>
                                                        <button type="button" onclick="toggleEditMode({{ $modifier->id }})"
                                                            class="flex-1 px-3 py-1.5 bg-gray-50 hover:bg-white text-gray-800 rounded-lg transition text-xs border border-gray-300">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-gray-800-muted text-sm text-center py-4">No portions added yet</p>
                                    <p class="text-gray-800-muted/60 text-xs text-center">Add portions if this item comes in
                                        different sizes</p>
                                @endforelse
                            </div>
                        </div>
                        <!-- End Portions Section -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let specialPriceCount = {{ $item->itemPrices()->whereNull('item_modifier_id')->count() }};
        let newPortionSpecialPriceCount = 0;
        let editPortionSpecialPriceCounts = {};
        let recipeCount = {{ $item->itemRecipes->count() }};
        let newPortionRecipeCount = 0;
        let editPortionRecipeCounts = {};

        // Raw materials data from server
        const rawMaterials = @json($rawMaterials);

        // Get list of already selected raw material IDs for item recipes
        function getSelectedRecipeIds(containerId, excludeRowId = null) {
            const container = document.getElementById(containerId);
            const selects = container.querySelectorAll('select');
            const selectedIds = [];
            selects.forEach(select => {
                const rowId = select.closest('[id^="recipe-"]')?.id;
                if (select.value && rowId !== excludeRowId) {
                    selectedIds.push(parseInt(select.value));
                }
            });
            return selectedIds;
        }

        // Get available options for a dropdown (excluding already selected items)
        function getRecipeOptionsHtml(containerId, excludeRowId = null, selectedValue = null) {
            const selectedIds = getSelectedRecipeIds(containerId, excludeRowId);
            let optionsHtml = '<option value="">-- Select Stock Item --</option>';
            rawMaterials.forEach(item => {
                const isSelected = selectedValue == item.id;
                const isDisabled = selectedIds.includes(item.id) && !isSelected;
                if (!isDisabled) {
                    optionsHtml += `<option value="${item.id}" data-unit="${item.unit_abbreviation}" ${isSelected ? 'selected' : ''}>${item.item_name}</option>`;
                }
            });
            return optionsHtml;
        }

        // Update all recipe dropdowns to exclude already selected items
        function refreshRecipeDropdowns(containerId) {
            const container = document.getElementById(containerId);
            const selects = container.querySelectorAll('select');
            selects.forEach(select => {
                const rowId = select.closest('[id^="recipe-"]')?.id;
                const currentValue = select.value;
                select.innerHTML = getRecipeOptionsHtml(containerId, rowId, currentValue);
            });
        }

        // Toggle Recipe section when Finished Goods checkbox is changed
        function toggleFinishedGoodsRecipeEdit() {
            const isFinishedGoods = document.getElementById('isFinishedGoodsEdit').checked;
            const recipeSection = document.getElementById('recipeSection');

            // Disable/enable all portion recipe sections in existing portions
            const portionRecipeSections = document.querySelectorAll('[id^="portionRecipes-"], [id^="editPortionRecipes-"]');
            const portionRecipeButtons = document.querySelectorAll('[onclick*="addPortionRecipeRow"], [onclick*="addNewPortionRecipe"], [onclick*="addEditPortionRecipe"]');

            if (isFinishedGoods) {
                // Disable item Recipe section
                recipeSection.classList.add('opacity-50', 'pointer-events-none');
                // Disable new portion recipe section
                const newPortionRecipes = document.getElementById('newPortionRecipes');
                if (newPortionRecipes) {
                    newPortionRecipes.closest('.border-amber-200')?.classList.add('opacity-50', 'pointer-events-none');
                }
                // Disable all portion recipe sections and buttons
                portionRecipeButtons.forEach(btn => {
                    btn.closest('.border-amber-200')?.classList.add('opacity-50', 'pointer-events-none');
                });
            } else {
                // Only enable item recipe if portions are not enabled
                const hasPortions = document.getElementById('hasPortionsEdit')?.checked;
                if (!hasPortions) {
                    recipeSection.classList.remove('opacity-50', 'pointer-events-none');
                }
                // Enable new portion recipe section
                const newPortionRecipes = document.getElementById('newPortionRecipes');
                if (newPortionRecipes) {
                    newPortionRecipes.closest('.border-amber-200')?.classList.remove('opacity-50', 'pointer-events-none');
                }
                // Enable all portion recipe sections
                portionRecipeButtons.forEach(btn => {
                    btn.closest('.border-amber-200')?.classList.remove('opacity-50', 'pointer-events-none');
                });
            }
        }

        // Recipe Management for Item
        function addRecipeRow() {
            recipeCount++;
            const container = document.getElementById('recipesList');

            const recipeDiv = document.createElement('div');
            recipeDiv.id = `recipe-new-${recipeCount}`;
            recipeDiv.className = 'flex gap-2 items-center bg-white p-2 rounded border border-amber-200 shadow-sm';

            let optionsHtml = getRecipeOptionsHtml('recipesList');

            recipeDiv.innerHTML = `
                    <div class="flex-1 grid grid-cols-3 gap-2">
                        <div class="col-span-1">
                            <select name="recipes[new][${recipeCount}][main_stock_item_id]" required
                                onchange="updateNewRecipeUnit(${recipeCount}); refreshRecipeDropdowns('recipesList');"
                                class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-amber-300 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 text-sm">
                                ${optionsHtml}
                            </select>
                        </div>
                        <div>
                            <input type="number" name="recipes[new][${recipeCount}][quantity]" step="0.001" min="0.001" required
                                placeholder="Quantity"
                                onkeydown="handleNewRecipeTabKey(event, ${recipeCount})"
                                class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-amber-300 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 text-sm">
                        </div>
                        <div class="flex items-center">
                            <span id="recipeUnit-new-${recipeCount}" class="text-sm text-gray-600 font-medium px-2">--</span>
                        </div>
                    </div>
                    <button type="button" onclick="removeNewRecipe(${recipeCount})" 
                        class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;

            container.appendChild(recipeDiv);
        }

        function removeNewRecipe(id) {
            const element = document.getElementById(`recipe-new-${id}`);
            if (element) {
                element.remove();
                refreshRecipeDropdowns('recipesList');
            }
        }

        function removeExistingRecipe(id) {
            const element = document.getElementById(`recipe-existing-${id}`);
            if (element) {
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'recipes[delete][]';
                deleteInput.value = id;
                document.querySelector('form').appendChild(deleteInput);
                element.remove();
                refreshRecipeDropdowns('recipesList');
            }
        }

        function updateNewRecipeUnit(rowId) {
            const select = document.querySelector(`select[name="recipes[new][${rowId}][main_stock_item_id]"]`);
            const unitSpan = document.getElementById(`recipeUnit-new-${rowId}`);
            const selectedOption = select.options[select.selectedIndex];
            unitSpan.textContent = selectedOption.dataset.unit || '--';
        }

        function handleNewRecipeTabKey(event, currentId) {
            if (event.key === 'Tab' && !event.shiftKey) {
                const lastRecipe = document.querySelector('#recipesList > div:last-child');
                if (lastRecipe && lastRecipe.id === `recipe-new-${currentId}`) {
                    event.preventDefault();
                    addRecipeRow();
                    setTimeout(() => {
                        const newSelect = document.querySelector(`select[name="recipes[new][${recipeCount}][main_stock_item_id]"]`);
                        if (newSelect) newSelect.focus();
                    }, 50);
                }
            }
        }

        // Toggle Portions Section
        function togglePortionSectionEdit() {
            const hasPortions = document.getElementById('hasPortionsEdit').checked;
            const portionsSection = document.getElementById('portionsSectionEdit');
            const defaultPriceInput = document.querySelector('input[name="price"]');
            const specialPricesSection = document.getElementById('specialPricesSection');
            const recipeSection = document.getElementById('recipeSection');

            if (hasPortions) {
                portionsSection.classList.remove('hidden');
                if (defaultPriceInput) {
                    defaultPriceInput.disabled = true;
                    defaultPriceInput.classList.add('opacity-50', 'cursor-not-allowed');
                }
                if (specialPricesSection) {
                    specialPricesSection.classList.add('opacity-50', 'pointer-events-none');
                }
                if (recipeSection) {
                    recipeSection.classList.add('opacity-50', 'pointer-events-none');
                }
            } else {
                portionsSection.classList.add('hidden');
                if (defaultPriceInput) {
                    defaultPriceInput.disabled = false;
                    defaultPriceInput.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                if (specialPricesSection) {
                    specialPricesSection.classList.remove('opacity-50', 'pointer-events-none');
                }
                if (recipeSection) {
                    recipeSection.classList.remove('opacity-50', 'pointer-events-none');
                }
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            togglePortionSectionEdit();
            toggleFinishedGoodsRecipeEdit();
        });

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
                priceInput.addEventListener('input', function () {
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
                    class="px-2 py-1.5 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 text-xs">
                    <option value="pickme" selected>Pick Me</option>
                </select>
                <input type="number" name="portion_special_prices[${newPortionSpecialPriceCount}][price]" step="0.01" min="0" required
                    placeholder="Price" class="w-20 px-2 py-1.5 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 text-xs">
                <button type="button" onclick="removeNewPortionSpecialPrice(${newPortionSpecialPriceCount})" 
                    class="px-1.5 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                priceInput.addEventListener('input', function () {
                    hiddenInput.value = this.value;
                });
            } else if (hiddenInput) {
                hiddenInput.remove();
            }
        }

        // Recipe Management for New Portion
        function addNewPortionRecipe() {
            newPortionRecipeCount++;
            const container = document.getElementById('newPortionRecipes');

            let optionsHtml = '<option value="">-- Select --</option>';
            rawMaterials.forEach(item => {
                optionsHtml += `<option value="${item.id}" data-unit="${item.unit_abbreviation}">${item.item_name}</option>`;
            });

            const recipeDiv = document.createElement('div');
            recipeDiv.id = `newPortionRecipe-${newPortionRecipeCount}`;
            recipeDiv.className = 'flex gap-1 items-center';

            recipeDiv.innerHTML = `
                    <select name="portion_recipes[${newPortionRecipeCount}][main_stock_item_id]" required
                        onchange="updateNewPortionRecipeUnit(${newPortionRecipeCount})"
                        class="flex-1 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                        ${optionsHtml}
                    </select>
                    <input type="number" name="portion_recipes[${newPortionRecipeCount}][quantity]" step="0.001" min="0.001" required
                        placeholder="Qty"
                        onkeydown="handleNewPortionRecipeTabKey(event, ${newPortionRecipeCount})"
                        class="w-16 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                    <span id="newPortionRecipeUnit-${newPortionRecipeCount}" class="text-xs text-gray-600 w-8">--</span>
                    <button type="button" onclick="removeNewPortionRecipe(${newPortionRecipeCount})" 
                        class="px-1.5 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;

            container.appendChild(recipeDiv);
        }

        function removeNewPortionRecipe(id) {
            const element = document.getElementById(`newPortionRecipe-${id}`);
            if (element) element.remove();
        }

        function updateNewPortionRecipeUnit(recipeId) {
            const select = document.querySelector(`select[name="portion_recipes[${recipeId}][main_stock_item_id]"]`);
            const unitSpan = document.getElementById(`newPortionRecipeUnit-${recipeId}`);
            const selectedOption = select.options[select.selectedIndex];
            unitSpan.textContent = selectedOption.dataset.unit || '--';
        }

        function handleNewPortionRecipeTabKey(event, currentId) {
            if (event.key === 'Tab' && !event.shiftKey) {
                const container = document.getElementById('newPortionRecipes');
                const lastRecipe = container.querySelector(':scope > div:last-child');
                if (lastRecipe && lastRecipe.id === `newPortionRecipe-${currentId}`) {
                    event.preventDefault();
                    addNewPortionRecipe();
                    setTimeout(() => {
                        const newSelect = document.querySelector(`select[name="portion_recipes[${newPortionRecipeCount}][main_stock_item_id]"]`);
                        if (newSelect) newSelect.focus();
                    }, 50);
                }
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
                    class="px-2 py-1.5 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 text-xs">
                    <option value="pickme" selected>Pick Me</option>
                </select>
                <input type="number" name="modifier_special_prices[new][${modifierId}][${priceId}][price]" step="0.01" min="0" required
                    placeholder="Price" class="w-20 px-2 py-1.5 bg-purple-50 text-gray-800 rounded-lg border border-purple-200 focus:outline-none focus:border-purple-500 text-xs">
                <input type="hidden" name="modifier_special_prices[new][${modifierId}][${priceId}][modifier_id]" value="${modifierId}">
                <button type="button" onclick="removeEditPortionSpecialPrice(${modifierId}, ${priceId})" 
                    class="px-1.5 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    priceInput.addEventListener('input', function () {
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

        // Recipe Management for Edit Portion
        function addEditPortionRecipe(modifierId) {
            if (!editPortionRecipeCounts[modifierId]) {
                editPortionRecipeCounts[modifierId] = 0;
            }
            editPortionRecipeCounts[modifierId]++;

            const container = document.getElementById(`editPortionRecipes-${modifierId}`);
            const recipeId = editPortionRecipeCounts[modifierId];

            let optionsHtml = '<option value="">-- Select --</option>';
            rawMaterials.forEach(item => {
                optionsHtml += `<option value="${item.id}" data-unit="${item.unit_abbreviation}">${item.item_name}</option>`;
            });

            const recipeDiv = document.createElement('div');
            recipeDiv.id = `modifierRecipe-${modifierId}-${recipeId}`;
            recipeDiv.className = 'flex gap-1 items-center';

            recipeDiv.innerHTML = `
                    <select name="modifier_recipes[new][${modifierId}][${recipeId}][main_stock_item_id]" required
                        onchange="updateEditPortionRecipeUnit(${modifierId}, ${recipeId})"
                        class="flex-1 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                        ${optionsHtml}
                    </select>
                    <input type="number" name="modifier_recipes[new][${modifierId}][${recipeId}][quantity]" step="0.001" min="0.001" required
                        placeholder="Qty"
                        onkeydown="handleEditPortionRecipeTabKey(event, ${modifierId}, ${recipeId})"
                        class="w-16 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                    <span id="editPortionRecipeUnit-${modifierId}-${recipeId}" class="text-xs text-gray-600 w-8">--</span>
                    <button type="button" onclick="removeEditPortionRecipe(${modifierId}, ${recipeId})" 
                        class="px-1.5 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;

            container.appendChild(recipeDiv);
        }

        function removeEditPortionRecipe(modifierId, recipeId) {
            const element = document.getElementById(`modifierRecipe-${modifierId}-${recipeId}`);
            if (element) element.remove();
        }

        function removeExistingModifierRecipe(id) {
            const element = document.getElementById(`modifierRecipe-existing-${id}`);
            if (element) {
                const form = element.closest('form');
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'modifier_recipes[delete][]';
                deleteInput.value = id;
                if (form) form.appendChild(deleteInput);
                element.remove();
            }
        }

        function updateEditPortionRecipeUnit(modifierId, recipeId) {
            const select = document.querySelector(`select[name="modifier_recipes[new][${modifierId}][${recipeId}][main_stock_item_id]"]`);
            const unitSpan = document.getElementById(`editPortionRecipeUnit-${modifierId}-${recipeId}`);
            if (select && unitSpan) {
                const selectedOption = select.options[select.selectedIndex];
                unitSpan.textContent = selectedOption.dataset.unit || '--';
            }
        }

        function handleEditPortionRecipeTabKey(event, modifierId, currentId) {
            if (event.key === 'Tab' && !event.shiftKey) {
                const container = document.getElementById(`editPortionRecipes-${modifierId}`);
                const lastRecipe = container.querySelector(':scope > div:last-child');
                if (lastRecipe && lastRecipe.id === `modifierRecipe-${modifierId}-${currentId}`) {
                    event.preventDefault();
                    addEditPortionRecipe(modifierId);
                    setTimeout(() => {
                        const newRecipeId = editPortionRecipeCounts[modifierId];
                        const newSelect = document.querySelector(`select[name="modifier_recipes[new][${modifierId}][${newRecipeId}][main_stock_item_id]"]`);
                        if (newSelect) newSelect.focus();
                    }, 50);
                }
            }
        }
    </script>
@endsection