<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\ItemModifier;
use App\Models\ItemPrice;
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
        return view('menu.categories.index', compact('categories'));
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

        $category->delete();
        return redirect()->route('menu.categories.index')->with('success', 'Category deleted successfully!');
    }

    /**
     * Show form to create a new item.
     */
    public function createItem()
    {
        $categories = Category::all();
        $kitchenStations = KitchenStation::all();

        return view('menu.items.create', compact('categories', 'kitchenStations'));
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

        // If has portions, create modifiers with independent prices
        if ($request->has('portions') && is_array($request->portions)) {
            foreach ($request->portions as $portionIndex => $portion) {
                if (!empty($portion['name']) && isset($portion['price'])) {
                    $modifier = $item->modifiers()->create([
                        'name' => $portion['name'],
                        'type' => 'size',
                        'price_adjustment' => $portion['price'], // Store as independent price
                        'is_active' => true,
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
        $categories = Category::all();
        $kitchenStations = KitchenStation::all();
        $item->load('modifiers');

        return view('menu.items.edit', compact('item', 'categories', 'kitchenStations'));
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
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['price'] = $validated['price'] ?? $item->price;
        $validated['is_finished_goods'] = $request->has('is_finished_goods');

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
