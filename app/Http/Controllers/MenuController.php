<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\ItemModifier;
use App\Models\ItemPrice;
use App\Models\ItemRecipe;
use App\Models\MainStockItem;
use App\Models\KitchenStation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    /**
     * Display menu management page.
     */
    public function index()
    {
        $categories = Category::with(['items.modifiers'])->get();
        $items = Item::with(['category', 'modifiers'])->orderBy('display_order')->get();

        return view('menu.index', compact('categories', 'items'));
    }

    /**
     * Display category management page.
     */
    public function indexCategories()
    {
        $categories = Category::withCount('items')->orderBy('display_order')->get();
        $nextDisplayOrder = (Category::max('display_order') ?? 0) + 1;
        return view('menu.categories.index', compact('categories', 'nextDisplayOrder'));
    }

    /**
     * Show form to create a new category.
     */
    public function createCategory()
    {
        return view('menu.categories.create');
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Handle display order collision - shift existing categories up if needed
        if (isset($validated['display_order'])) {
            $displayOrder = $validated['display_order'];

            // Check if this display order is already used
            $existingCategory = Category::where('display_order', $displayOrder)->first();

            if ($existingCategory) {
                // Shift all categories with display_order >= the requested order up by 1
                Category::where('display_order', '>=', $displayOrder)
                    ->orderBy('display_order', 'desc')
                    ->each(function ($cat) {
                        $cat->display_order = $cat->display_order + 1;
                        $cat->save();
                    });
            }
        }

        Category::create($validated);

        return redirect()->route('menu.categories.index')->with('success', 'Category created successfully!');
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Handle display order collision - shift existing categories if needed
        if (isset($validated['display_order'])) {
            $newDisplayOrder = $validated['display_order'];
            $oldDisplayOrder = $category->display_order;

            // Only handle collision if the display order actually changed
            if ($newDisplayOrder != $oldDisplayOrder) {
                // Check if this display order is already used by another category
                $existingCategory = Category::where('display_order', $newDisplayOrder)
                    ->where('id', '!=', $category->id)
                    ->first();

                if ($existingCategory) {
                    // Shift all categories with display_order >= the new order (except current) up by 1
                    Category::where('display_order', '>=', $newDisplayOrder)
                        ->where('id', '!=', $category->id)
                        ->orderBy('display_order', 'desc')
                        ->each(function ($cat) {
                            $cat->display_order = $cat->display_order + 1;
                            $cat->save();
                        });
                }
            }
        }

        $category->update($validated);

        return redirect()->route('menu.categories.index')->with('success', 'Category updated successfully!');
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(Category $category)
    {
        if ($category->items()->count() > 0) {
            return redirect()->route('menu.categories.index')->with('error', 'Cannot delete category with existing items!');
        }

        $deletedDisplayOrder = $category->display_order;
        $category->delete();

        // Shift all categories with display_order > deleted order down by 1 to fill the gap
        if ($deletedDisplayOrder) {
            Category::where('display_order', '>', $deletedDisplayOrder)
                ->orderBy('display_order', 'asc')
                ->each(function ($cat) {
                    $cat->display_order = $cat->display_order - 1;
                    $cat->save();
                });
        }

        return redirect()->route('menu.categories.index')->with('success', 'Category deleted successfully!');
    }

    /**
     * Show form to create a new item.
     */
    public function createItem()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $kitchenStations = KitchenStation::all();
        $rawMaterials = MainStockItem::active()
            ->ofType('raw_material')
            ->orderBy('item_name')
            ->get();

        return view('menu.items.create', compact('categories', 'kitchenStations', 'rawMaterials'));
    }

    /**
     * Store a new item with portions.
     */
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'special_prices' => 'nullable|array',
            'has_portions' => 'nullable|boolean',
            'is_finished_goods' => 'nullable|boolean',
            'is_stock_count' => 'nullable|boolean',
            'portions' => 'nullable|array',
            'portions.*.name' => 'required_with:portions|string|max:255',
            'portions.*.price' => 'required_with:portions|numeric|min:0',
            'portions.*.special_prices' => 'nullable|array',
        ]);

        // Set defaults
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_available'] = true;
        $validated['is_featured'] = false;
        $validated['is_finished_goods'] = $request->has('is_finished_goods');
        $validated['is_stock_count'] = $request->has('is_stock_count');
        $validated['pork_available'] = $request->has('pork_available');
        $validated['display_order'] = 0;
        $validated['price'] = $validated['price'] ?? 0;

        // Create the item
        $item = Item::create($validated);

        // Store special prices for the item
        if ($request->has('special_prices.new')) {
            foreach ($request->input('special_prices.new') as $priceData) {
                $item->itemPrices()->create([
                    'price_type' => $priceData['type'],
                    'price' => $priceData['price'],
                    'item_modifier_id' => null,
                ]);
            }
        }

        // Store recipes for the item (only when no portions)
        if ($request->has('recipes') && is_array($request->recipes)) {
            foreach ($request->recipes as $recipeData) {
                if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                    $item->recipes()->create([
                        'main_stock_item_id' => $recipeData['main_stock_item_id'],
                        'quantity' => $recipeData['quantity'],
                        'item_modifier_id' => null,
                    ]);
                }
            }
        }

        // If has portions, create modifiers with independent prices
        if ($request->has('portions') && is_array($request->portions)) {
            foreach ($request->portions as $portionIndex => $portion) {
                if (!empty($portion['name']) && isset($portion['price'])) {
                    $modifier = $item->modifiers()->create([
                        'name' => $portion['name'],
                        'type' => 'size',
                        'price_adjustment' => $portion['price'], // Store as independent price
                        'is_active' => true,
                        'pork_available' => isset($portion['pork_available']),
                    ]);

                    // Store special prices for this portion if provided
                    if (isset($portion['special_prices']) && is_array($portion['special_prices'])) {
                        foreach ($portion['special_prices'] as $priceData) {
                            $item->itemPrices()->create([
                                'price_type' => $priceData['type'],
                                'price' => $priceData['price'],
                                'item_modifier_id' => $modifier->id,
                            ]);
                        }
                    }

                    // Store recipes for this portion if provided
                    if (isset($portion['recipes']) && is_array($portion['recipes'])) {
                        foreach ($portion['recipes'] as $recipeData) {
                            if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                                $item->recipes()->create([
                                    'main_stock_item_id' => $recipeData['main_stock_item_id'],
                                    'quantity' => $recipeData['quantity'],
                                    'item_modifier_id' => $modifier->id,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return redirect()->route('menu.index')->with('success', 'Item created successfully!');
    }

    /**
     * Show form to edit an item.
     */
    public function editItem(Item $item)
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $kitchenStations = KitchenStation::all();
        $rawMaterials = MainStockItem::active()
            ->ofType('raw_material')
            ->orderBy('item_name')
            ->get();
        $item->load(['modifiers.recipes.mainStockItem', 'itemRecipes.mainStockItem']);

        return view('menu.items.edit', compact('item', 'categories', 'kitchenStations', 'rawMaterials'));
    }

    /**
     * Update an item.
     */
    public function updateItem(Request $request, Item $item)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'special_prices' => 'nullable|array',
            'is_finished_goods' => 'nullable|boolean',
            'is_stock_count' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['price'] = $validated['price'] ?? $item->price;
        $validated['is_finished_goods'] = $request->has('is_finished_goods');
        $validated['is_stock_count'] = $request->has('is_stock_count');
        $validated['pork_available'] = $request->has('pork_available');

        $item->update($validated);

        // Handle special prices deletion
        if ($request->has('special_prices.delete')) {
            ItemPrice::whereIn('id', $request->input('special_prices.delete'))->delete();
        }

        // Handle existing special prices updates
        if ($request->has('special_prices.existing')) {
            foreach ($request->input('special_prices.existing') as $priceData) {
                if (isset($priceData['id'])) {
                    $itemPrice = ItemPrice::find($priceData['id']);
                    if ($itemPrice && $itemPrice->item_id === $item->id) {
                        $itemPrice->update([
                            'price_type' => $priceData['type'],
                            'price' => $priceData['price'],
                        ]);
                    }
                }
            }
        }

        // Handle new special prices
        if ($request->has('special_prices.new')) {
            foreach ($request->input('special_prices.new') as $priceData) {
                $item->itemPrices()->create([
                    'price_type' => $priceData['type'],
                    'price' => $priceData['price'],
                    'item_modifier_id' => null,
                ]);
            }
        }

        // Handle recipe deletion
        if ($request->has('recipes.delete')) {
            ItemRecipe::whereIn('id', $request->input('recipes.delete'))->delete();
        }

        // Handle existing recipe updates
        if ($request->has('recipes.existing')) {
            foreach ($request->input('recipes.existing') as $recipeData) {
                if (isset($recipeData['id'])) {
                    $recipe = ItemRecipe::find($recipeData['id']);
                    if ($recipe && $recipe->item_id === $item->id) {
                        $recipe->update([
                            'main_stock_item_id' => $recipeData['main_stock_item_id'],
                            'quantity' => $recipeData['quantity'],
                        ]);
                    }
                }
            }
        }

        // Handle new recipes
        if ($request->has('recipes.new')) {
            foreach ($request->input('recipes.new') as $recipeData) {
                if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                    $item->recipes()->create([
                        'main_stock_item_id' => $recipeData['main_stock_item_id'],
                        'quantity' => $recipeData['quantity'],
                        'item_modifier_id' => null,
                    ]);
                }
            }
        }

        return redirect()->route('menu.items.edit', $item)->with('success', 'Item updated successfully!');
    }

    /**
     * Delete an item.
     */
    public function destroyItem(Item $item)
    {
        $item->delete();

        return redirect()->route('menu.index')->with('success', 'Item deleted successfully!');
    }

    /**
     * Store a new modifier for an item.
     */
    public function storeModifier(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'portion_special_prices' => 'nullable|array',
        ]);

        $modifier = $item->modifiers()->create([
            'name' => $validated['name'],
            'type' => 'size',
            'price_adjustment' => $validated['price'],
            'is_active' => true,
            'pork_available' => $request->has('pork_available'),
        ]);

        // Store special prices for this portion
        if ($request->has('portion_special_prices')) {
            foreach ($request->input('portion_special_prices') as $priceData) {
                $item->itemPrices()->create([
                    'price_type' => $priceData['type'],
                    'price' => $priceData['price'],
                    'item_modifier_id' => $modifier->id,
                ]);
            }
        }

        // Store recipes for this portion
        if ($request->has('portion_recipes')) {
            foreach ($request->input('portion_recipes') as $recipeData) {
                if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                    $item->recipes()->create([
                        'main_stock_item_id' => $recipeData['main_stock_item_id'],
                        'quantity' => $recipeData['quantity'],
                        'item_modifier_id' => $modifier->id,
                    ]);
                }
            }
        }

        return redirect()->route('menu.items.edit', $item)->with('success', 'Portion added successfully!');
    }

    /**
     * Update a modifier.
     */
    public function updateModifier(Request $request, ItemModifier $modifier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'modifier_special_prices' => 'nullable|array',
        ]);

        $modifier->update([
            'name' => $validated['name'],
            'price_adjustment' => $validated['price'],
            'pork_available' => $request->has('pork_available'),
        ]);

        // Handle special prices deletion
        if ($request->has('modifier_special_prices.delete')) {
            ItemPrice::whereIn('id', $request->input('modifier_special_prices.delete'))->delete();
        }

        // Handle existing special prices updates
        if ($request->has('modifier_special_prices.existing')) {
            foreach ($request->input('modifier_special_prices.existing') as $priceData) {
                if (isset($priceData['id'])) {
                    $itemPrice = ItemPrice::find($priceData['id']);
                    if ($itemPrice && $itemPrice->item_modifier_id === $modifier->id) {
                        $itemPrice->update([
                            'price_type' => $priceData['type'],
                            'price' => $priceData['price'],
                        ]);
                    }
                }
            }
        }

        // Handle new special prices
        if ($request->has('modifier_special_prices.new')) {
            foreach ($request->input('modifier_special_prices.new') as $modifierId => $prices) {
                if ($modifierId == $modifier->id) {
                    foreach ($prices as $priceData) {
                        $modifier->item->itemPrices()->create([
                            'price_type' => $priceData['type'],
                            'price' => $priceData['price'],
                            'item_modifier_id' => $modifier->id,
                        ]);
                    }
                }
            }
        }

        // Handle recipe deletion for this modifier
        if ($request->has('modifier_recipes.delete')) {
            ItemRecipe::whereIn('id', $request->input('modifier_recipes.delete'))->delete();
        }

        // Handle existing recipe updates for this modifier
        if ($request->has('modifier_recipes.existing')) {
            foreach ($request->input('modifier_recipes.existing') as $recipeData) {
                if (isset($recipeData['id'])) {
                    $recipe = ItemRecipe::find($recipeData['id']);
                    if ($recipe && $recipe->item_modifier_id === $modifier->id) {
                        $recipe->update([
                            'main_stock_item_id' => $recipeData['main_stock_item_id'],
                            'quantity' => $recipeData['quantity'],
                        ]);
                    }
                }
            }
        }

        // Handle new recipes for this modifier
        if ($request->has('modifier_recipes.new')) {
            foreach ($request->input('modifier_recipes.new') as $modId => $recipes) {
                if ($modId == $modifier->id) {
                    foreach ($recipes as $recipeData) {
                        if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                            $modifier->item->recipes()->create([
                                'main_stock_item_id' => $recipeData['main_stock_item_id'],
                                'quantity' => $recipeData['quantity'],
                                'item_modifier_id' => $modifier->id,
                            ]);
                        }
                    }
                }
            }
        }

        return redirect()->route('menu.items.edit', $modifier->item)->with('success', 'Portion updated successfully!');
    }

    /**
     * Delete a modifier.
     */
    public function destroyModifier(ItemModifier $modifier)
    {
        $item = $modifier->item;
        $modifier->delete();

        return redirect()->route('menu.items.edit', $item)->with('success', 'Portion deleted successfully!');
    }
}
