<?php

namespace App\Http\Controllers;

use App\Models\Wastage;
use App\Models\MainStockItem;
use App\Models\CashierSubStock;
use App\Models\CashierFgStockLog;
use App\Models\CashierRmStockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WastageController extends Controller
{
    /**
     * Show the cashier wastage page.
     */
    public function index()
    {
        // Get all active main stock items that have cashier sub-stock
        $items = CashierSubStock::with('mainStockItem')
            ->whereHas('mainStockItem', function ($q) {
                $q->active();
            })
            ->get()
            ->map(function ($stock) {
                $item = $stock->mainStockItem;
                $price = $item->price ?? 0;

                // For finished goods with linked item, get price from menu item
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

                return [
                    'id' => $item->id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                    'type' => $item->item_type,
                    'unit' => $item->unit_abbreviation,
                    'price' => round($price, 2),
                    'current_qty' => round((float) $stock->quantity, 3),
                ];
            })
            ->values();

        $reasons = Wastage::REASONS;

        return view('stock.cashier.wastage', compact('items', 'reasons'));
    }

    /**
     * Get item details via AJAX (for real-time stock refresh).
     */
    public function getItemDetails(MainStockItem $item)
    {
        $subStock = CashierSubStock::where('main_stock_item_id', $item->id)->first();
        $currentQty = $subStock ? (float) $subStock->quantity : 0;

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

        return response()->json([
            'id' => $item->id,
            'code' => $item->item_code,
            'name' => $item->item_name,
            'type' => $item->item_type,
            'unit' => $item->unit_abbreviation,
            'price' => round($price, 2),
            'current_qty' => round($currentQty, 3),
        ]);
    }

    /**
     * Process wastage - deduct stock and log.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:main_stock_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.reason' => 'required|string|max:100',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $wastageId = Wastage::generateWastageId();
            $userId = Auth::id();
            $savedCount = 0;
            $totalWastageAmount = 0;

            foreach ($request->items as $itemData) {
                $mainStockItem = MainStockItem::findOrFail($itemData['item_id']);

                // Get current cashier sub-stock
                $subStock = CashierSubStock::where('main_stock_item_id', $mainStockItem->id)->first();
                $currentQty = $subStock ? (float) $subStock->quantity : 0;
                $wasteQty = (float) $itemData['quantity'];

                // Check if enough stock available
                if ($currentQty < $wasteQty) {
                    throw new \Exception("Not enough stock for {$mainStockItem->item_name}. Available: {$currentQty} {$mainStockItem->unit_abbreviation}");
                }

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

                $wastageAmount = $wasteQty * $price;
                $newQty = $currentQty - $wasteQty;

                // Create wastage record
                Wastage::create([
                    'wastage_id' => $wastageId,
                    'wastage_date' => now(),
                    'main_stock_item_id' => $mainStockItem->id,
                    'item_name' => $mainStockItem->item_name,
                    'item_code' => $mainStockItem->item_code,
                    'item_type' => $mainStockItem->item_type,
                    'quantity_before' => $currentQty,
                    'quantity_wasted' => $wasteQty,
                    'quantity_after' => $newQty,
                    'unit' => $mainStockItem->unit_abbreviation,
                    'price' => $price,
                    'wastage_amount' => $wastageAmount,
                    'reason' => $itemData['reason'],
                    'notes' => $itemData['notes'] ?? null,
                    'performed_by' => $userId,
                ]);

                // Deduct from cashier sub-stock
                $subStockRecord = CashierSubStock::firstOrCreate(
                    ['main_stock_item_id' => $mainStockItem->id],
                    ['quantity' => 0, 'last_updated_by' => $userId]
                );
                $subStockRecord->quantity = $newQty;
                $subStockRecord->last_updated_by = $userId;
                $subStockRecord->save();

                // Log stock change based on item type
                $reasonLabel = Wastage::REASONS[$itemData['reason']] ?? $itemData['reason'];
                $logNotes = "Wastage: {$reasonLabel} - Qty: {$wasteQty}" . ($itemData['notes'] ? ' - ' . $itemData['notes'] : '');

                if ($mainStockItem->item_type === 'finished_good') {
                    CashierFgStockLog::log(
                        $mainStockItem->id,
                        'wastage',
                        $currentQty,
                        $newQty,
                        'wastage',
                        $wastageId,
                        $logNotes,
                        $userId
                    );
                } else {
                    CashierRmStockLog::log(
                        $mainStockItem->id,
                        'wastage',
                        $currentQty,
                        $newQty,
                        'wastage',
                        $wastageId,
                        $logNotes,
                        $userId
                    );
                }

                Log::info('Wastage Recorded', [
                    'wastage_id' => $wastageId,
                    'item' => $mainStockItem->item_name,
                    'type' => $mainStockItem->item_type,
                    'qty_before' => $currentQty,
                    'qty_wasted' => $wasteQty,
                    'qty_after' => $newQty,
                    'reason' => $itemData['reason'],
                    'amount' => $wastageAmount,
                ]);

                $totalWastageAmount += $wastageAmount;
                $savedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Wastage recorded successfully! {$savedCount} item(s) processed. Total wastage: Rs. " . number_format($totalWastageAmount, 2),
                'wastage_id' => $wastageId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wastage Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record wastage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wastage history for cashier (their own records).
     */
    public function history(Request $request)
    {
        $query = Wastage::with(['mainStockItem', 'performer'])
            ->where('performed_by', Auth::id())
            ->orderBy('wastage_date', 'desc');

        if ($request->filled('from_date')) {
            $query->whereDate('wastage_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('wastage_date', '<=', $request->to_date);
        }

        $wastages = $query->limit(200)->get()->map(function ($w) {
            return [
                'id' => $w->id,
                'wastage_id' => $w->wastage_id,
                'date_display' => $w->wastage_date->format('d M Y h:i A'),
                'item_name' => $w->item_name,
                'item_code' => $w->item_code ?? '-',
                'item_type' => $w->item_type,
                'quantity_wasted' => (float) $w->quantity_wasted,
                'unit' => $w->unit,
                'price' => number_format($w->price, 2),
                'wastage_amount' => number_format($w->wastage_amount, 2),
                'reason' => $w->reason_label,
                'notes' => $w->notes ?? '-',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $wastages,
            'total' => $wastages->count(),
        ]);
    }
}
