<?php

namespace App\Http\Controllers;

use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\Item;
use App\Models\Category;
use App\Models\MainStock;
use App\Models\RestaurantStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockRequestController extends Controller
{
    /**
     * Display the cashier stock request page.
     */
    public function cashierIndex()
    {
        // Get all items marked as Finished Goods (for dropdown selection)
        $finishedGoodsItems = Item::where('is_available', true)
            ->where('is_finished_goods', true)
            ->with(['category', 'modifiers'])
            ->orderBy('name')
            ->get();

        // Prepare dropdown items for JavaScript
        $finishedGoodsDropdown = collect();
        foreach ($finishedGoodsItems as $item) {
            if ($item->modifiers->count() > 0) {
                // Item has modifiers - add each as separate option
                foreach ($item->modifiers as $modifier) {
                    $finishedGoodsDropdown->push([
                        'id' => $item->id . '-' . $modifier->id,
                        'item_id' => $item->id,
                        'modifier_id' => $modifier->id,
                        'name' => $item->name . ' (' . $modifier->name . ')',
                        'category' => $item->category->name ?? 'Unknown',
                        'available_qty' => 0,
                        'unit' => 'pcs',
                    ]);
                }
            } else {
                // Item without modifiers
                $finishedGoodsDropdown->push([
                    'id' => $item->id . '-null',
                    'item_id' => $item->id,
                    'modifier_id' => null,
                    'name' => $item->name,
                    'category' => $item->category->name ?? 'Unknown',
                    'available_qty' => 0,
                    'unit' => 'pcs',
                ]);
            }
        }

        // Get restaurant stock quantities for lookup (what cashier has at restaurant)
        $restaurantStockQty = RestaurantStock::whereHas('item', function ($query) {
            $query->where('is_finished_goods', true);
        })
            ->get()
            ->keyBy(function ($stock) {
                return $stock->item_id . '-' . ($stock->item_modifier_id ?? 'null');
            });

        // Get main stock items with restaurant stock qty for display (items available to request)
        $stockItems = MainStock::with(['item', 'item.category', 'itemModifier'])
            ->whereHas('item', function ($query) {
                $query->where('is_finished_goods', true);
            })
            ->orderBy('item_id')
            ->get()
            ->map(function ($stock) use ($restaurantStockQty) {
                $displayName = $stock->item->name;
                if ($stock->itemModifier) {
                    $displayName .= ' (' . $stock->itemModifier->name . ')';
                }

                // Get restaurant stock qty for this item+modifier combination
                $key = $stock->item_id . '-' . ($stock->item_modifier_id ?? 'null');
                $restStock = $restaurantStockQty->get($key);
                $availableQty = $restStock ? $restStock->quantity : 0;

                return [
                    'id' => $stock->id,
                    'item_id' => $stock->item_id,
                    'modifier_id' => $stock->item_modifier_id,
                    'name' => $displayName,
                    'category' => $stock->item->category->name ?? 'Unknown',
                    'available_qty' => $availableQty,
                    'unit' => $stock->unit,
                ];
            });

        // Get restaurant stock (cashier's available stock at the restaurant) for display section
        $restaurantStock = RestaurantStock::with(['item', 'item.category', 'itemModifier'])
            ->whereHas('item', function ($query) {
                $query->where('is_finished_goods', true);
            })
            ->where('quantity', '>', 0)
            ->orderBy('item_id')
            ->get();

        $myRequests = StockRequest::with('items')
            ->byCashier(Auth::id())
            ->latest()
            ->paginate(10);

        return view('stock.cashier.index', compact('finishedGoodsDropdown', 'myRequests', 'stockItems', 'restaurantStock'));
    }

    /**
     * Display the cashier's restaurant stock page (table view).
     */
    public function cashierStock()
    {
        // Get all restaurant stock for Finished Goods items
        $search = trim((string) request()->get('search', ''));
        $stocks = RestaurantStock::with(['item', 'item.category', 'itemModifier'])
            ->whereHas('item', function ($query) {
                $query->where('is_finished_goods', true);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('item', function ($itemQuery) use ($search) {
                        $itemQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('item_code', 'like', '%' . $search . '%');
                    })->orWhereHas('itemModifier', function ($modifierQuery) use ($search) {
                        $modifierQuery->where('name', 'like', '%' . $search . '%');
                    });
                });
            })
            ->orderBy('item_id')
            ->get();

        return view('stock.cashier.stock', compact('stocks', 'search'));
    }

    /**
     * Store a new stock request from cashier.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.modifier_id' => 'nullable|exists:item_modifiers,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $stockRequest = StockRequest::create([
                'cashier_id' => Auth::id(),
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $menuItem = Item::find($item['item_id']);
                $itemName = $menuItem->name;

                // Include modifier name if present
                if (!empty($item['modifier_id'])) {
                    $modifier = \App\Models\ItemModifier::find($item['modifier_id']);
                    if ($modifier) {
                        $itemName .= ' (' . $modifier->name . ')';
                    }
                }

                StockRequestItem::create([
                    'stock_request_id' => $stockRequest->id,
                    'item_id' => $item['item_id'],
                    'item_modifier_id' => $item['modifier_id'] ?? null,
                    'item_name' => $itemName,
                    'requested_quantity' => $item['quantity'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock request created successfully!',
                'request_number' => $stockRequest->request_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the supervisor stock management page.
     */
    public function supervisorIndex()
    {
        $pendingRequests = StockRequest::with(['items', 'cashier'])
            ->pending()
            ->latest()
            ->get();

        $acceptedRequests = StockRequest::with(['items', 'cashier', 'supervisor'])
            ->accepted()
            ->latest()
            ->get();

        $rejectedRequests = StockRequest::with(['items', 'cashier', 'supervisor'])
            ->rejected()
            ->latest()
            ->get();

        $counts = [
            'pending' => $pendingRequests->count(),
            'accepted' => $acceptedRequests->count(),
            'rejected' => $rejectedRequests->count(),
        ];

        return view('stock.supervisor.index', compact('pendingRequests', 'acceptedRequests', 'rejectedRequests', 'counts'));
    }

    /**
     * Get a specific stock request details.
     */
    public function show(StockRequest $stockRequest)
    {
        $stockRequest->load(['items.item', 'cashier', 'supervisor']);

        return response()->json([
            'success' => true,
            'data' => $stockRequest,
        ]);
    }

    /**
     * Supervisor responds to a stock request.
     */
    public function supervisorRespond(Request $request, StockRequest $stockRequest)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
            'items' => 'required_if:action,accept|array',
            'items.*.id' => 'required_if:action,accept|exists:stock_request_items,id',
            'items.*.approved_quantity' => 'required_if:action,accept|integer|min:0',
            'supervisor_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            if ($request->action === 'reject') {
                $stockRequest->update([
                    'status' => 'rejected',
                    'supervisor_id' => Auth::id(),
                    'supervisor_notes' => $request->supervisor_notes,
                    'responded_at' => now(),
                ]);

                // Update all items to rejected
                $stockRequest->items()->update(['status' => 'rejected']);
            } else {
                $allApproved = true;
                $noneApproved = true;

                foreach ($request->items as $itemData) {
                    $item = StockRequestItem::find($itemData['id']);
                    $approvedQty = $itemData['approved_quantity'];

                    if ($approvedQty == 0) {
                        $item->update([
                            'approved_quantity' => 0,
                            'status' => 'rejected',
                        ]);
                    } elseif ($approvedQty < $item->requested_quantity) {
                        $item->update([
                            'approved_quantity' => $approvedQty,
                            'status' => 'partially_approved',
                        ]);
                        $allApproved = false;
                        $noneApproved = false;
                    } else {
                        $item->update([
                            'approved_quantity' => $approvedQty,
                            'status' => 'approved',
                        ]);
                        $noneApproved = false;
                    }
                }

                $status = $noneApproved ? 'rejected' : ($allApproved ? 'accepted' : 'partially_accepted');

                $stockRequest->update([
                    'status' => $status,
                    'supervisor_id' => Auth::id(),
                    'supervisor_notes' => $request->supervisor_notes,
                    'responded_at' => now(),
                    'cashier_response' => 'pending',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Response submitted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to respond: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cashier responds to supervisor's decision.
     * When accepted, stock is transferred from MainStock to RestaurantStock.
     */
    public function cashierRespond(Request $request, StockRequest $stockRequest)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        // Verify the request belongs to this cashier
        if ($stockRequest->cashier_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $isAccepted = $request->action === 'accept';

            $stockRequest->update([
                'cashier_response' => $isAccepted ? 'accepted' : 'rejected',
                'cashier_response_notes' => $request->notes,
                'cashier_responded_at' => now(),
            ]);

            // If cashier accepts, transfer stock from MainStock to RestaurantStock
            if ($isAccepted) {
                $stockRequest->load('items');

                foreach ($stockRequest->items as $item) {
                    // Only process approved items with quantity > 0
                    if ($item->status === 'approved' || $item->status === 'partially_approved') {
                        $approvedQty = $item->approved_quantity ?? $item->requested_quantity;

                        if ($approvedQty > 0) {
                            // Get MainStock record with correct item and modifier
                            $mainStockQuery = MainStock::where('item_id', $item->item_id);
                            if ($item->item_modifier_id) {
                                $mainStockQuery->where('item_modifier_id', $item->item_modifier_id);
                            } else {
                                $mainStockQuery->whereNull('item_modifier_id');
                            }
                            $mainStock = $mainStockQuery->first();

                            // Deduct from MainStock if it exists and has sufficient quantity
                            if ($mainStock && $mainStock->quantity >= $approvedQty) {
                                $mainStock->deductStock($approvedQty, Auth::id());
                            }

                            // Get or create RestaurantStock record with modifier and add quantity
                            $restaurantStock = RestaurantStock::getOrCreateForItem(
                                $item->item_id,
                                $item->item_modifier_id,
                                $mainStock ? $mainStock->unit : 'pcs'
                            );
                            $restaurantStock->addStock($approvedQty, Auth::id());
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isAccepted
                    ? 'Stock transfer completed successfully!'
                    : 'Request has been rejected.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to respond: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cashier's requests with filters.
     */
    public function getCashierRequests(Request $request)
    {
        $query = StockRequest::with('items')
            ->byCashier(Auth::id());

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Get items for dropdown.
     */
    public function getItems()
    {
        $categories = Category::with(['items' => function ($query) {
            $query->where('is_available', true)->orderBy('name');
        }])->active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
