<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Get all categories with items
     */
    public function categories()
    {
        $categories = Category::with(['items' => function ($query) {
            $query->available();
        }])
        ->active()
        ->ordered()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get all available items
     */
    public function items(Request $request)
    {
        $query = Item::with(['category', 'modifiers', 'kitchenStation'])
            ->available();

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Featured items only
        if ($request->boolean('featured')) {
            $query->featured();
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Get item details
     */
    public function show(Item $item)
    {
        $item->load(['category', 'modifiers', 'kitchenStation']);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
     * Create new category (Admin only)
     */
    public function storeCategory(Request $request)
    {
        $this->authorize('create-menu');

        $request->validate([
            'name' => 'required|string|max:100|unique:categories',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Create new item (Admin only)
     */
    public function storeItem(Request $request)
    {
        $this->authorize('create-menu');

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'kitchen_station_id' => 'required|exists:kitchen_stations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'preparation_time' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
        ]);

        $item = Item::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully',
            'data' => $item->load(['category', 'kitchenStation']),
        ], 201);
    }

    /**
     * Update item (Admin only)
     */
    public function updateItem(Request $request, Item $item)
    {
        $this->authorize('edit-menu');

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $item->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item,
        ]);
    }

    /**
     * Delete item (Admin only)
     */
    public function destroyItem(Item $item)
    {
        $this->authorize('delete-menu');

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully',
        ]);
    }
}
