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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1
                            class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent">
                            Add New Menu Item</h1>
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

                        <!-- Finished Goods & Stock Count Checkboxes -->
                        <div class="flex gap-4">
                            <div class="flex-1 bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_finished_goods" id="isFinishedGoods" value="1" {{ old('is_finished_goods') ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 bg-gray-50 border-gray-300 rounded focus:ring-blue-500"
                                        onchange="toggleFinishedGoodsRecipe()">
                                    <span class="ml-3 text-gray-800 font-semibold">Finished Goods</span>
                                </label>
                            </div>

                            <div class="flex-1 bg-green-50 p-4 rounded-lg border border-green-200">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_stock_count" value="1" {{ old('is_stock_count', true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-green-600 bg-gray-50 border-gray-300 rounded focus:ring-green-500">
                                    <span class="ml-3 text-gray-800 font-semibold">Stock Count</span>
                                </label>
                            </div>
                        </div>

                        <!-- Default Price (disabled when has portions) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-800-muted mb-2">Default Price (Rs.)
                                *</label>
                            <input type="number" name="price" id="defaultPrice" value="{{ old('price', 0) }}" step="0.01"
                                min="0" required
                                class="w-full px-4 py-2 bg-gray-50 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Special Prices Section -->
                        <div id="specialPricesSection"
                            class="bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 rounded-lg border border-purple-200">
                            <div class="flex justify-between items-center mb-3">
                                <label class="text-sm font-semibold text-gray-800">Special Prices</label>
                                <button type="button" onclick="addSpecialPrice()"
                                    class="px-3 py-1.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white rounded-lg transition text-sm font-semibold flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Special Price
                                </button>
                            </div>
                            <div id="specialPricesList" class="space-y-2">
                                <!-- Special price fields will be added here -->
                            </div>
                        </div>

                        <!-- Recipe Section (for items without portions) -->
                        <div id="recipeSection"
                            class="bg-gradient-to-br from-amber-50 to-orange-100/50 p-4 rounded-lg border border-amber-200">
                            <div class="flex justify-between items-center mb-3">
                                <label class="text-sm font-semibold text-gray-800">Recipe (Raw Materials)</label>
                                <button type="button" onclick="addRecipeRow()"
                                    class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:shadow-lg hover:shadow-amber-500/50 text-white rounded-lg transition text-sm font-semibold flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Recipe
                                </button>
                            </div>
                            <div id="recipesList" class="space-y-2">
                                <!-- Recipe rows will be added here -->
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
                        </div>

                        <!-- Portions Section (shown when HAS portions) -->
                        <div id="portionsSection" class="{{ old('has_portions') ? '' : 'hidden' }}">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-800 mb-3">Add Portions/Sizes</h3>
                                <p class="text-gray-800-muted text-sm mb-4">Add different sizes with their individual prices
                                    and recipes</p>

                                <div id="portionsList" class="space-y-3">
                                    <!-- Portion fields will be added here -->
                                </div>

                                <button type="button" onclick="addPortionField()"
                                    class="mt-3 w-full px-4 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold">
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
                        <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold">
                            Create Item
                        </button>
                        <a href="{{ route('menu.index') }}"
                            class="px-6 py-2 bg-gray-50 text-gray-800 rounded-lg hover:bg-white border border-gray-300 transition font-semibold">
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
        let recipeCount = 0;
        let portionRecipeCounts = {};

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
        function toggleFinishedGoodsRecipe() {
            const isFinishedGoods = document.getElementById('isFinishedGoods').checked;
            const recipeSection = document.getElementById('recipeSection');

            // Disable/enable all portion recipe sections
            const portionRecipeSections = document.querySelectorAll('[id^="portionRecipes-"]');
            const portionRecipeButtons = document.querySelectorAll('[onclick^="addPortionRecipeRow"]');

            if (isFinishedGoods) {
                // Disable item Recipe section
                recipeSection.classList.add('opacity-50', 'pointer-events-none');
                // Disable all portion recipe sections and buttons
                portionRecipeSections.forEach(section => {
                    section.closest('.border-t.border-amber-200')?.classList.add('opacity-50', 'pointer-events-none');
                });
                portionRecipeButtons.forEach(btn => {
                    btn.closest('.border-t.border-amber-200')?.classList.add('opacity-50', 'pointer-events-none');
                });
            } else {
                // Only enable item recipe if portions are not enabled
                const hasPortions = document.getElementById('hasPortions')?.checked;
                if (!hasPortions) {
                    recipeSection.classList.remove('opacity-50', 'pointer-events-none');
                }
                // Enable all portion recipe sections
                portionRecipeSections.forEach(section => {
                    section.closest('.border-t.border-amber-200')?.classList.remove('opacity-50', 'pointer-events-none');
                });
                portionRecipeButtons.forEach(btn => {
                    btn.closest('.border-t.border-amber-200')?.classList.remove('opacity-50', 'pointer-events-none');
                });
            }
        }

        // Recipe Management for Item
        function addRecipeRow() {
            recipeCount++;
            const container = document.getElementById('recipesList');

            const recipeDiv = document.createElement('div');
            recipeDiv.id = `recipe-${recipeCount}`;
            recipeDiv.className = 'flex gap-2 items-center bg-white p-2 rounded border border-amber-200 shadow-sm';

            let optionsHtml = getRecipeOptionsHtml('recipesList');

            recipeDiv.innerHTML = `
                <div class="flex-1 grid grid-cols-3 gap-2">
                    <div class="col-span-1">
                        <select name="recipes[${recipeCount}][main_stock_item_id]" required
                            onchange="updateRecipeUnit(${recipeCount}); refreshRecipeDropdowns('recipesList');"
                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-amber-300 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 text-sm">
                            ${optionsHtml}
                        </select>
                    </div>
                    <div>
                        <input type="number" name="recipes[${recipeCount}][quantity]" step="0.001" min="0.001" required
                            placeholder="Quantity" 
                            onkeydown="handleRecipeTabKey(event, ${recipeCount})"
                            class="w-full px-3 py-2 bg-gray-50 text-gray-800 rounded-lg border border-amber-300 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 text-sm">
                    </div>
                    <div class="flex items-center">
                        <span id="recipeUnit-${recipeCount}" class="text-sm text-gray-600 font-medium px-2">--</span>
                    </div>
                </div>
                <button type="button" onclick="removeRecipeRow(${recipeCount})" 
                    class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;

            container.appendChild(recipeDiv);
        }

        function removeRecipeRow(id) {
            const element = document.getElementById(`recipe-${id}`);
            if (element) {
                element.remove();
                refreshRecipeDropdowns('recipesList');
            }
        }

        function updateRecipeUnit(rowId) {
            const select = document.querySelector(`select[name="recipes[${rowId}][main_stock_item_id]"]`);
            const unitSpan = document.getElementById(`recipeUnit-${rowId}`);
            const selectedOption = select.options[select.selectedIndex];
            unitSpan.textContent = selectedOption.dataset.unit || '--';
        }

        function handleRecipeTabKey(event, currentId) {
            if (event.key === 'Tab' && !event.shiftKey) {
                const lastRecipe = document.querySelector('#recipesList > div:last-child');
                if (lastRecipe && lastRecipe.id === `recipe-${currentId}`) {
                    event.preventDefault();
                    addRecipeRow();
                    setTimeout(() => {
                        const newSelect = document.querySelector(`select[name="recipes[${recipeCount}][main_stock_item_id]"]`);
                        if (newSelect) newSelect.focus();
                    }, 50);
                }
            }
        }

        // Portion Recipe Management
        function getPortionSelectedRecipeIds(portionId, excludeRecipeId = null) {
            const container = document.getElementById(`portionRecipes-${portionId}`);
            if (!container) return [];
            const selects = container.querySelectorAll('select');
            const selectedIds = [];
            selects.forEach(select => {
                const recipeId = select.closest('[id^="portionRecipe-"]')?.id;
                if (select.value && recipeId !== `portionRecipe-${portionId}-${excludeRecipeId}`) {
                    selectedIds.push(parseInt(select.value));
                }
            });
            return selectedIds;
        }

        function getPortionRecipeOptionsHtml(portionId, excludeRecipeId = null, selectedValue = null) {
            const selectedIds = getPortionSelectedRecipeIds(portionId, excludeRecipeId);
            let optionsHtml = '<option value="">-- Select --</option>';
            rawMaterials.forEach(item => {
                const isSelected = selectedValue == item.id;
                const isDisabled = selectedIds.includes(item.id) && !isSelected;
                if (!isDisabled) {
                    optionsHtml += `<option value="${item.id}" data-unit="${item.unit_abbreviation}" ${isSelected ? 'selected' : ''}>${item.item_name}</option>`;
                }
            });
            return optionsHtml;
        }

        function refreshPortionRecipeDropdowns(portionId) {
            const container = document.getElementById(`portionRecipes-${portionId}`);
            if (!container) return;
            const selects = container.querySelectorAll('select');
            selects.forEach(select => {
                const match = select.closest('[id^="portionRecipe-"]')?.id.match(/portionRecipe-(\d+)-(\d+)/);
                if (match) {
                    const recipeId = match[2];
                    const currentValue = select.value;
                    select.innerHTML = getPortionRecipeOptionsHtml(portionId, recipeId, currentValue);
                }
            });
        }

        function addPortionRecipeRow(portionId) {
            if (!portionRecipeCounts[portionId]) {
                portionRecipeCounts[portionId] = 0;
            }
            portionRecipeCounts[portionId]++;

            const container = document.getElementById(`portionRecipes-${portionId}`);
            const recipeId = portionRecipeCounts[portionId];

            let optionsHtml = getPortionRecipeOptionsHtml(portionId);

            const recipeDiv = document.createElement('div');
            recipeDiv.id = `portionRecipe-${portionId}-${recipeId}`;
            recipeDiv.className = 'flex gap-1 items-center';

            recipeDiv.innerHTML = `
                <select name="portions[${portionId}][recipes][${recipeId}][main_stock_item_id]" required
                    onchange="updatePortionRecipeUnit(${portionId}, ${recipeId}); refreshPortionRecipeDropdowns(${portionId});"
                    class="flex-1 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                    ${optionsHtml}
                </select>
                <input type="number" name="portions[${portionId}][recipes][${recipeId}][quantity]" step="0.001" min="0.001" required
                    placeholder="Qty"
                    onkeydown="handlePortionRecipeTabKey(event, ${portionId}, ${recipeId})"
                    class="w-16 px-2 py-1.5 bg-amber-50 text-gray-800 rounded-lg border border-amber-200 focus:outline-none focus:border-amber-500 text-xs">
                <span id="portionRecipeUnit-${portionId}-${recipeId}" class="text-xs text-gray-600 w-8">--</span>
                <button type="button" onclick="removePortionRecipeRow(${portionId}, ${recipeId})" 
                    class="px-1.5 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;

            container.appendChild(recipeDiv);
        }

        function removePortionRecipeRow(portionId, recipeId) {
            const element = document.getElementById(`portionRecipe-${portionId}-${recipeId}`);
            if (element) {
                element.remove();
                refreshPortionRecipeDropdowns(portionId);
            }
        }

        function updatePortionRecipeUnit(portionId, recipeId) {
            const select = document.querySelector(`select[name="portions[${portionId}][recipes][${recipeId}][main_stock_item_id]"]`);
            const unitSpan = document.getElementById(`portionRecipeUnit-${portionId}-${recipeId}`);
            const selectedOption = select.options[select.selectedIndex];
            unitSpan.textContent = selectedOption.dataset.unit || '--';
        }

        function handlePortionRecipeTabKey(event, portionId, currentId) {
            if (event.key === 'Tab' && !event.shiftKey) {
                const container = document.getElementById(`portionRecipes-${portionId}`);
                const lastRecipe = container.querySelector(':scope > div:last-child');
                if (lastRecipe && lastRecipe.id === `portionRecipe-${portionId}-${currentId}`) {
                    event.preventDefault();
                    addPortionRecipeRow(portionId);
                    setTimeout(() => {
                        const newRecipeId = portionRecipeCounts[portionId];
                        const newSelect = document.querySelector(`select[name="portions[${portionId}][recipes][${newRecipeId}][main_stock_item_id]"]`);
                        if (newSelect) newSelect.focus();
                    }, 50);
                }
            }
        }

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
                priceInput.addEventListener('input', function () {
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
                priceInput.addEventListener('input', function () {
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
            const recipeSection = document.getElementById('recipeSection');

            if (hasPortions) {
                portionsSection.classList.remove('hidden');
                defaultPriceInput.disabled = true;
                defaultPriceInput.classList.add('opacity-50', 'cursor-not-allowed');
                defaultPriceInput.value = 0;

                // Disable special prices section when portions are enabled
                if (specialPricesSection) {
                    specialPricesSection.classList.add('opacity-50', 'pointer-events-none');
                }

                // Disable recipe section when portions are enabled (each portion has its own recipe)
                if (recipeSection) {
                    recipeSection.classList.add('opacity-50', 'pointer-events-none');
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

                // Re-enable recipe section when portions are disabled
                if (recipeSection) {
                    recipeSection.classList.remove('opacity-50', 'pointer-events-none');
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
            <div class="border-t border-amber-200 pt-2 mt-2">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-xs font-semibold text-amber-700">Recipe (Raw Materials)</label>
                    <button type="button" onclick="addPortionRecipeRow(${portionCount})" class="px-2 py-1 bg-gradient-to-r from-amber-500 to-orange-500 hover:shadow-md hover:shadow-amber-500/30 text-white rounded text-xs font-semibold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add
                    </button>
                </div>
                <div id="portionRecipes-${portionCount}" class="space-y-1">
                    <!-- Recipes for this portion -->
                </div>
            </div>
        `;

            portionsList.appendChild(portionDiv);

            // Apply finished goods restriction to the newly added portion's recipe section
            toggleFinishedGoodsRecipe();
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
        document.addEventListener('DOMContentLoaded', function () {
            togglePortionFields();
            toggleFinishedGoodsRecipe();
        });
    </script>
@endsection