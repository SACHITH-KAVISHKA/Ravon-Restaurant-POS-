<?php

namespace App\Http\Controllers;

use App\Models\MainStockItem;
use App\Models\ItemRecipe;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RmSalesReportController extends Controller
{
    /**
     * Display the RM Sales Report summary view
     */
    public function index(Request $request)
    {
        $rawMaterials = MainStockItem::notDeleted()->orderBy('item_name')->get();
        return view('reports.rm-sales', compact('rawMaterials'));
    }

    /**
     * Filter RM Sales data via AJAX
     */
    public function filter(Request $request)
    {
        $request->validate([
            'raw_material_id' => 'required|exists:main_stock_items,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $rawMaterialId = $request->raw_material_id;
        $fromDate = $request->start_date;
        $toDate = $request->end_date;

        $rawMaterial = MainStockItem::notDeleted()->findOrFail($rawMaterialId);

        // Find all recipes where this RM is used
        $recipes = ItemRecipe::with(['item', 'modifier'])
            ->where('main_stock_item_id', $rawMaterialId)
            ->get();

        $reportData = [];
        $totalQuantityUsed = 0;

        foreach ($recipes as $recipe) {
            // Only consider recipes that are linked to an item
            if (!$recipe->item) {
                continue;
            }

            // Find all completed order items for this item AND modifier in the date range
            $query = OrderItem::with(['order'])
                ->whereHas('order', function ($q) use ($fromDate, $toDate) {
                    $q->where('status', 'completed')
                      ->where('is_paid', true)
                      ->whereBetween(DB::raw('DATE(completed_at)'), [$fromDate, $toDate]);
                })
                ->where('status', '!=', 'cancelled')
                ->where('item_id', $recipe->item_id);

            // Filter by modifier/portion if the recipe has one
            if ($recipe->item_modifier_id) {
                $query->where('item_modifier_id', $recipe->item_modifier_id);
            } else {
                $query->whereNull('item_modifier_id');
            }

            $orderItems = $query->get();
            $totalQtySold = $orderItems->sum('quantity');

            if ($totalQtySold > 0) {
                // Determine item display name
                $posItemName = $recipe->item->name;
                if ($recipe->modifier) {
                    $posItemName .= ' - ' . $recipe->modifier->name;
                }

                $totalRmUsed = $totalQtySold * $recipe->quantity;
                $totalQuantityUsed += $totalRmUsed;
                
                // Keep the order details to display in a modal if requested by action button
                $reportData[] = [
                    'item_id' => $recipe->item_id,
                    'item_modifier_id' => $recipe->item_modifier_id,
                    'pos_item_name' => $posItemName,
                    'recipe_qty_per_item' => $recipe->quantity,
                    'unit' => $rawMaterial->unit_abbreviation,
                    'total_sold' => $totalQtySold,
                    'total_rm_used' => $totalRmUsed
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'summary' => [
                'raw_material_name' => $rawMaterial->item_name,
                'total_rm_used' => $totalQuantityUsed,
                'unit' => $rawMaterial->unit_abbreviation,
            ]
        ]);
    }

    /**
     * Get detailed order actions for a specific POS Item/Portion via AJAX
     */
    public function getOrderDetails(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'item_modifier_id' => 'nullable|exists:item_modifiers,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $itemId = $request->item_id;
        $modifierId = $request->item_modifier_id;
        $fromDate = $request->start_date;
        $toDate = $request->end_date;

        $query = OrderItem::with([
            'order' => function ($query) {
                $query->select('id', 'order_number', 'completed_at', 'customer_name');
            }
        ])
            ->whereHas('order', function ($query) use ($fromDate, $toDate) {
                $query->where('status', 'completed')
                    ->where('is_paid', true)
                    ->whereBetween(DB::raw('DATE(completed_at)'), [$fromDate, $toDate]);
            })
            ->where('item_id', $itemId)
            ->where('status', '!=', 'cancelled');

        if ($modifierId) {
            $query->where('item_modifier_id', $modifierId);
        } else {
            $query->whereNull('item_modifier_id');
        }

        $transactions = $query->get()
            ->map(function ($orderItem) {
                return [
                    'order_number' => $orderItem->order->order_number ?? 'N/A',
                    'customer' => $orderItem->order->customer_name ?: 'Walk-in Customer',
                    'quantity' => (int) $orderItem->quantity,
                    'completed_at' => $orderItem->order->completed_at ?
                        $orderItem->order->completed_at->format('Y-m-d H:i:s') : 'N/A'
                ];
            });

        return response()->json([
            'success' => true,
            'transactions' => $transactions,
        ]);
    }
}
