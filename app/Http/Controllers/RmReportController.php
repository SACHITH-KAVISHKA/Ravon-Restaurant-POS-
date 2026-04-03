<?php

namespace App\Http\Controllers;

use App\Models\ItemRecipe;
use App\Models\MainStockItem;
use Illuminate\Http\Request;

class RmReportController extends Controller
{
    /**
     * Display the RM Report page.
     */
    public function index(Request $request)
    {
        $rawMaterials = MainStockItem::ofType('raw_material')->orderBy('item_name')->get();
        $selectedRmId = $request->input('raw_material_id');
        $recipes = collect();

        if ($selectedRmId) {
            $recipes = ItemRecipe::with(['item', 'modifier'])
                ->where('main_stock_item_id', $selectedRmId)
                ->get();
        }

        return view('menu.rm-report', compact('rawMaterials', 'selectedRmId', 'recipes'));
    }

    /**
     * Update a recipe's raw material or quantity.
     */
    public function update(Request $request, ItemRecipe $recipe)
    {
        $validated = $request->validate([
            'main_stock_item_id' => 'required|exists:main_stock_items,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        $recipe->update($validated);

        return redirect()->back()->with('success', 'Recipe updated successfully!');
    }

    /**
     * Remove a recipe.
     */
    public function destroy(ItemRecipe $recipe)
    {
        $recipe->delete();

        return redirect()->back()->with('success', 'Raw material removed from POS item successfully!');
    }
}
