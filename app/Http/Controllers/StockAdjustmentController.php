<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\MainStockItem;
use App\Models\CashierSubStock;
use App\Models\CashierFgStockLog;
use App\Models\CashierRmStockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{
    /**
     * Show the stock adjustment page.
     */
    public function index()
    {
        // Get all active main stock items (both RM and Finished Goods)
        $items = MainStockItem::where('is_active', true)
            ->notDeleted()
            ->orderBy('item_name')
            ->get()
            ->map(function ($item) {
                // Get price from main item price list
                $price = $item->price ?? 0;

                // For finished goods with linked item, get price from menu item
                if ($item->item_type === 'finished_good' && $item->linked_item_id) {
                    $menuItem = \App\Models\Item::find($item->linked_item_id);
                    if ($menuItem) {
                        // If modifier exists, get modifier price
                        if ($item->linked_item_modifier_id) {
                            $modifier = \App\Models\ItemModifier::find($item->linked_item_modifier_id);
                            if ($modifier) {
                                $price = $modifier->getPriceByType('default') ?? $price;
                            }
                        } else {
                            $price = $menuItem->getPriceByType('default') ?? $price;
                        }
                    }
                }

                // Get current stock based on item type
                $systemQty = 0;
                $stockLocation = 'RM';

                if ($item->item_type === 'finished_good') {
                    // Finished goods → Cashier FG stock
                    $subStock = CashierSubStock::where('main_stock_item_id', $item->id)->first();
                    $systemQty = $subStock ? (float) $subStock->quantity : 0;
                    $stockLocation = 'CASHIER';
                } else {
                    // Raw materials → Cashier side RM stock (CashierSubStock)
                    $subStock = CashierSubStock::where('main_stock_item_id', $item->id)->first();
                    $systemQty = $subStock ? (float) $subStock->quantity : 0;
                    $stockLocation = 'RM';
                }

                return [
                    'id' => $item->id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                    'type' => $item->item_type,
                    'unit' => $item->unit_abbreviation,
                    'price' => round($price, 2),
                    'system_qty' => round($systemQty, 3),
                    'stock_location' => $stockLocation,
                ];
            })
            ->values();

        return view('stock.supervisor.adjustment.index', compact('items'));
    }

    /**
     * Get item details via AJAX (for real-time stock refresh).
     */
    public function getItemDetails(MainStockItem $item)
    {
        $price = $item->price ?? 0;

        if ($item->item_type === 'finished_good' && $item->linked_item_id) {
            $menuItem = \App\Models\Item::find($item->linked_item_id);
            if ($menuItem) {
                if ($item->linked_item_modifier_id) {
                    $modifier = \App\Models\ItemModifier::find($item->linked_item_modifier_id);
                    if ($modifier) {
                        $price = $modifier->getPriceByType('default') ?? $price;
                    }
                } else {
                    $price = $menuItem->getPriceByType('default') ?? $price;
                }
            }
        }

        $systemQty = 0;
        $stockLocation = 'RM';

        if ($item->item_type === 'finished_good') {
            // Finished goods → Cashier FG stock
            $subStock = CashierSubStock::where('main_stock_item_id', $item->id)->first();
            $systemQty = $subStock ? (float) $subStock->quantity : 0;
            $stockLocation = 'CASHIER';
        } else {
            // Raw materials → Cashier side RM stock (CashierSubStock)
            $subStock = CashierSubStock::where('main_stock_item_id', $item->id)->first();
            $systemQty = $subStock ? (float) $subStock->quantity : 0;
            $stockLocation = 'RM';
        }

        return response()->json([
            'id' => $item->id,
            'code' => $item->item_code,
            'name' => $item->item_name,
            'type' => $item->item_type,
            'unit' => $item->unit_abbreviation,
            'price' => round($price, 2),
            'system_qty' => round($systemQty, 3),
            'stock_location' => $stockLocation,
        ]);
    }

    /**
     * Save the stock adjustment and update stock.
     */
    public function store(Request $request)
    {
        $request->validate([
            'adjustment_date' => 'required|date',
            'cashier_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:main_stock_items,id',
            'items.*.actual_qty' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $adjustmentId = StockAdjustment::generateAdjustmentId();
            $userId = Auth::id();
            $savedCount = 0;

            foreach ($request->items as $itemData) {
                $mainStockItem = MainStockItem::findOrFail($itemData['item_id']);

                // Determine stock location & system qty from CashierSubStock for both RM and FG
                $stockLocation = 'RM';
                $subStock = CashierSubStock::where('main_stock_item_id', $mainStockItem->id)->first();
                $systemQty = $subStock ? (float) $subStock->quantity : 0;

                if ($mainStockItem->item_type === 'finished_good') {
                    $stockLocation = 'CASHIER';
                }

                $actualQty = (float) $itemData['actual_qty'];
                $varianceQty = $actualQty - $systemQty;

                // Get price
                $price = $mainStockItem->price ?? 0;
                if ($mainStockItem->item_type === 'finished_good' && $mainStockItem->linked_item_id) {
                    $menuItem = \App\Models\Item::find($mainStockItem->linked_item_id);
                    if ($menuItem) {
                        if ($mainStockItem->linked_item_modifier_id) {
                            $modifier = \App\Models\ItemModifier::find($mainStockItem->linked_item_modifier_id);
                            if ($modifier) {
                                $price = $modifier->getPriceByType('default') ?? $price;
                            }
                        } else {
                            $price = $menuItem->getPriceByType('default') ?? $price;
                        }
                    }
                }

                $varianceAmount = $varianceQty * $price;
                $adjustmentType = $varianceQty >= 0 ? 'IN' : 'OUT';

                // Save adjustment log
                StockAdjustment::create([
                    'adjustment_id' => $adjustmentId,
                    'adjustment_date' => $request->adjustment_date,
                    'cashier_name' => $request->cashier_name,
                    'notes' => $request->notes,
                    'main_stock_item_id' => $mainStockItem->id,
                    'item_name' => $mainStockItem->item_name,
                    'system_qty' => $systemQty,
                    'actual_qty' => $actualQty,
                    'variance_qty' => $varianceQty,
                    'price' => $price,
                    'variance_amount' => $varianceAmount,
                    'adjustment_type' => $adjustmentType,
                    'stock_location' => $stockLocation,
                    'performed_by' => $userId,
                ]);

                // Update actual stock in CashierSubStock for both RM and FG
                $subStockRecord = CashierSubStock::firstOrCreate(
                    ['main_stock_item_id' => $mainStockItem->id],
                    ['quantity' => 0, 'last_updated_by' => $userId]
                );
                $subStockRecord->quantity = $actualQty;
                $subStockRecord->last_updated_by = $userId;
                $subStockRecord->save();

                // Log stock change based on item type
                if ($mainStockItem->item_type === 'finished_good') {
                    CashierFgStockLog::log(
                        $mainStockItem->id,
                        'adjustment',
                        $systemQty,
                        $actualQty,
                        'adjustment',
                        $adjustmentId,
                        'Manual adjustment by ' . ($request->cashier_name ?? 'Admin') . ($request->notes ? ' - ' . $request->notes : ''),
                        $userId
                    );
                } else {
                    CashierRmStockLog::log(
                        $mainStockItem->id,
                        'adjustment',
                        $systemQty,
                        $actualQty,
                        'adjustment',
                        $adjustmentId,
                        'Manual adjustment by ' . ($request->cashier_name ?? 'Admin') . ($request->notes ? ' - ' . $request->notes : ''),
                        $userId
                    );
                }

                Log::info('Stock Adjustment: Cashier sub-stock updated', [
                    'item' => $mainStockItem->item_name,
                    'stock_location' => $stockLocation,
                    'old_qty' => $systemQty,
                    'new_qty' => $actualQty,
                    'variance' => $varianceQty,
                ]);

                $savedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Stock adjustment saved successfully! {$savedCount} item(s) adjusted.",
                'adjustment_id' => $adjustmentId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock Adjustment Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save stock adjustment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show stock adjustment history page.
     */
    public function history()
    {
        $items = MainStockItem::where('is_active', true)
            ->notDeleted()
            ->orderBy('item_name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                ];
            })
            ->values();

        return view('stock.supervisor.adjustment.history', compact('items'));
    }

    /**
     * Get filtered adjustment history via AJAX.
     */
    public function getHistory(Request $request)
    {
        $query = StockAdjustment::with(['mainStockItem', 'performer'])
            ->orderBy('adjustment_date', 'desc');

        // Filter by item
        if ($request->filled('item_id')) {
            $query->where('main_stock_item_id', $request->item_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('adjustment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('adjustment_date', '<=', $request->to_date);
        }

        $adjustments = $query->limit(500)->get()->map(function ($adj) {
            return [
                'id' => $adj->id,
                'adjustment_id' => $adj->adjustment_id,
                'date_time' => $adj->adjustment_date->format('Y-m-d H:i:s'),
                'date_display' => $adj->adjustment_date->format('d M Y h:i A'),
                'cashier_name' => $adj->cashier_name ?? '-',
                'notes' => $adj->notes ?? '-',
                'item_name' => $adj->item_name,
                'item_code' => $adj->mainStockItem->item_code ?? '-',
                'system_qty' => number_format($adj->system_qty, 3),
                'actual_qty' => number_format($adj->actual_qty, 3),
                'variance_qty' => number_format($adj->variance_qty, 3),
                'price' => number_format($adj->price, 2),
                'variance_amount' => number_format($adj->variance_amount, 2),
                'adjustment_type' => $adj->adjustment_type,
                'stock_location' => $adj->stock_location,
                'performed_by' => $adj->performer->name ?? 'Admin',
                'quantity_display' => ($adj->variance_qty >= 0 ? '+' : '') . number_format($adj->variance_qty, 3),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $adjustments,
            'total' => $adjustments->count(),
        ]);
    }
}
