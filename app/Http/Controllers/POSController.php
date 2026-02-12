<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\CashierSubStock;
use App\Models\ItemRecipe;
use App\Models\ItemModifier;
use App\Models\VoidRecord;
use App\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    /**
     * Display the POS interface.
     */
    public function index()
    {
        $categories = Category::active()
            ->ordered()
            ->with([
                'availableItems' => function ($query) {
                    $query->available()->orderBy('display_order')
                        ->with(['modifiers.itemPrices', 'itemPrices']);
                }
            ])
            ->get();

        $tables = Table::orderByRaw("CAST(SUBSTRING(table_number, 2) AS UNSIGNED)")->get();

        return view('pos.index', compact('categories', 'tables'));
    }

    /**
     * Get item details for POS.
     */
    public function getItem($id)
    {
        $item = Item::with([
            'modifiers' => function ($query) {
                $query->where('is_active', true);
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'item' => $item
        ]);
    }

    /**
     * Get available tables
     */
    public function getAvailableTables()
    {
        $tables = Table::with('currentOrder')
            ->orderByRaw("CAST(SUBSTRING(table_number, 2) AS UNSIGNED)")
            ->get()
            ->map(function ($table) {
                return [
                    'id' => $table->id,
                    'table_number' => $table->table_number,
                    'status' => $table->status,
                    'is_available' => $table->status === 'available',
                    'current_order_id' => $table->current_order_id,
                ];
            });

        return response()->json([
            'success' => true,
            'tables' => $tables
        ]);
    }

    /**
     * Get open checks (orders)
     */
    public function getOpenChecks()
    {
        // Log the request for debugging
        Log::info('Open Checks requested', [
            'user_id' => Auth::id(),
            'user_name' => Auth::user() ? Auth::user()->name : 'Not logged in',
            'time' => now()->toDateTimeString(),
        ]);

        $openOrders = Order::with(['table', 'activeItems.item'])
            ->where('status', 'pending')
            ->where('is_paid', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                // Count only active items (not cancelled or deleted)
                $activeItemsCount = $order->orderItems->filter(function ($item) {
                    return !in_array($item->status, ['cancelled', 'deleted']);
                })->count();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'table_number' => $order->table ? $order->table->table_number : 'N/A',
                    'order_type' => $order->order_type,
                    'pickme_ref_number' => $order->pickme_ref_number,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at->format('M d, h:i A'),
                    'items_count' => $activeItemsCount,
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $openOrders
        ]);
    }

    /**
     * Get closed/completed orders (paid orders)
     */
    public function getClosedOrders()
    {
        // Get today's date (start of day and end of day)
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $closedOrders = Order::with(['table', 'activeItems.item', 'payment'])
            ->where(function ($q) {
                $q->where('status', 'completed')
                    ->orWhere('is_paid', true);
            })
            // Filter for TODAY only
            ->whereBetween('completed_at', [$todayStart, $todayEnd])
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(function ($order) {
                // Count only non-cancelled items
                $activeItemsCount = $order->orderItems->filter(function ($item) {
                    return $item->status !== 'cancelled';
                })->count();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'table_number' => $order->table ? $order->table->table_number : 'N/A',
                    'order_type' => $order->order_type,
                    'pickme_ref_number' => $order->pickme_ref_number,
                    'total_amount' => $order->total_amount,
                    // Format: Dec 08, 04:15 AM (using local timezone)
                    'completed_at' => $order->completed_at ? $order->completed_at->format('M d, h:i A') : 'N/A',
                    'items_count' => $activeItemsCount,
                    'payment_method' => $order->payment ? $order->payment->payment_method : 'N/A',
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $closedOrders
        ]);
    }

    /**
     * Get order details for editing
     */
    public function getOrder($orderId)
    {
        $order = Order::with([
            'orderItems' => function ($query) {
                $query->where('status', '!=', 'deleted')
                    ->orderBy('id', 'asc')  // Ensure consistent ordering
                    ->with(['item', 'modifiers.modifier']);
                // NO LIMIT - fetch ALL items
            },
            'table',
            'waiter',
            'payment'
        ])->findOrFail($orderId);

        // Transform orderItems to items format expected by frontend
        // Only include active items (not cancelled or deleted)
        $items = $order->orderItems
            ->filter(function ($orderItem) {
                return !in_array($orderItem->status, ['cancelled', 'deleted']);
            })
            ->map(function ($orderItem) {
                $modifiers = $orderItem->modifiers->map(function ($mod) {
                    return [
                        'id' => $mod->id,
                        'modifier_id' => $mod->modifier_id,
                        'name' => $mod->modifier->name ?? 'Modifier',
                        'price_adjustment' => $mod->price_adjustment,
                    ];
                })->toArray();

                return [
                    'item_id' => $orderItem->item_id,
                    'modifier_id' => $orderItem->item_modifier_id, // Include modifier_id for ID-based matching
                    'name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                    'item_name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                    'item_code' => $orderItem->item->item_code ?? '',
                    'price' => $orderItem->unit_price,
                    'unit_price' => $orderItem->unit_price,
                    'quantity' => $orderItem->quantity,
                    'subtotal' => $orderItem->subtotal,
                    'modifiers' => $modifiers,
                    'item' => [
                        'name' => $orderItem->item->name ?? 'Unknown Item',
                        'item_code' => $orderItem->item->item_code ?? ''
                    ]
                ];
            })->values(); // Reset array keys

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'table_id' => $order->table_id,
                'table_number' => $order->table ? $order->table->table_number : null,
                'pickme_ref_number' => $order->pickme_ref_number,
                'table' => $order->table ? [
                    'id' => $order->table->id,
                    'table_number' => $order->table->table_number
                ] : null,
                'waiter' => $order->waiter ? [
                    'id' => $order->waiter->id,
                    'name' => $order->waiter->name
                ] : null,
                'items' => $items,
                'orderItems' => $items,
                'order_items' => $items,
                'total_amount' => $order->total_amount,
                'subtotal' => $order->subtotal,
                'status' => $order->status,
                'payment' => $order->payment
            ]
        ]);
    }

    /**
     * Place order (Create new order or update existing)
     */
    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'order_type' => 'required|in:dine_in,takeaway,delivery,uber_eats,pickme',
            'table_id' => 'nullable|exists:tables,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'pickme_ref_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.modifier_id' => 'nullable|exists:item_modifiers,id', // For ID-based stock deduction
            'items.*.quantity' => 'required|integer|min:0', // Allow 0 for item removal
            'items.*.price' => 'required|numeric|min:0',
            'items.*.name' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $isNewOrder = empty($validated['order_id']);

            if ($isNewOrder) {
                // Filter out items with quantity 0 for new orders
                $validated['items'] = array_filter($validated['items'], function ($item) {
                    return $item['quantity'] > 0;
                });

                // Create new order
                $order = Order::create([
                    'order_type' => $validated['order_type'],
                    'table_id' => $validated['table_id'] ?? null,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'pickme_ref_number' => $validated['pickme_ref_number'] ?? null,
                    'waiter_id' => Auth::id(),
                    'created_by' => Auth::id(),
                    'status' => 'pending',
                    'is_paid' => false,
                    'placed_at' => now(),
                    'subtotal' => 0,
                    'total_amount' => 0,
                ]);

                // Log order creation
                OrderLog::logCreated($order, 'New order placed via POS');

                // Reserve table if dine-in
                if ($validated['table_id']) {
                    $table = Table::find($validated['table_id']);
                    $table->update([
                        'status' => 'ordered',
                        'current_order_id' => $order->id,
                    ]);
                }

                // Process all items as new
                $itemsToProcess = [];
                foreach ($validated['items'] as $itemData) {
                    $itemsToProcess[] = ['data' => $itemData, 'is_new' => true];
                }
            } else {
                // Update existing order
                $order = Order::findOrFail($validated['order_id']);

                // Get existing ACTIVE order items (not deleted/cancelled)
                $existingActiveItems = $order->orderItems
                    ->filter(function ($item) {
                        return !in_array($item->status, ['deleted', 'cancelled']);
                    });

                // Build lookup maps for matching
                // Primary key: item_id + modifier_id (most accurate)
                $existingByKey = $existingActiveItems->keyBy(function ($item) {
                    return $item->item_id . '_' . ($item->item_modifier_id ?? 'null');
                });

                // Secondary lookup: by item_id + display_name (fallback when modifier_id is missing)
                $existingByName = [];
                foreach ($existingActiveItems as $item) {
                    $nameKey = $item->item_id . '_' . ($item->item_display_name ?? '');
                    if (!isset($existingByName[$nameKey])) {
                        $existingByName[$nameKey] = $item;
                    }
                }

                Log::info('Order update - existing items:', [
                    'order_id' => $order->id,
                    'existing_keys' => $existingByKey->keys()->toArray(),
                    'existing_name_keys' => array_keys($existingByName),
                ]);

                $itemsToProcess = [];
                $matchedExistingIds = []; // Track which existing items were matched

                // Process each item from the request
                foreach ($validated['items'] as $itemData) {
                    $modifierId = $itemData['modifier_id'] ?? null;
                    $primaryKey = $itemData['item_id'] . '_' . ($modifierId ?? 'null');
                    $nameKey = $itemData['item_id'] . '_' . ($itemData['name'] ?? '');

                    Log::info('Processing request item:', [
                        'item_id' => $itemData['item_id'],
                        'modifier_id' => $modifierId,
                        'name' => $itemData['name'] ?? '',
                        'quantity' => $itemData['quantity'],
                        'primary_key' => $primaryKey,
                        'name_key' => $nameKey,
                    ]);

                    $matchedItem = null;

                    // Pass 1: Try exact match by item_id + modifier_id
                    if ($existingByKey->has($primaryKey)) {
                        $matchedItem = $existingByKey->get($primaryKey);
                        Log::info('Matched by primary key (item_id+modifier_id)', ['key' => $primaryKey, 'matched_id' => $matchedItem->id]);
                    }

                    // Pass 2: If no match and modifier_id is null, try matching by item_id + name
                    // This handles the case where frontend loses modifier_id but the item hasn't changed
                    if (!$matchedItem && $modifierId === null && isset($existingByName[$nameKey])) {
                        $candidate = $existingByName[$nameKey];
                        // Only use name-based match if this existing item hasn't been matched already
                        if (!in_array($candidate->id, $matchedExistingIds)) {
                            $matchedItem = $candidate;
                            // IMPORTANT: Inherit the modifier_id from the existing item
                            $modifierId = $matchedItem->item_modifier_id;
                            $itemData['modifier_id'] = $modifierId;
                            Log::info('Matched by name fallback, inherited modifier_id', [
                                'name_key' => $nameKey,
                                'matched_id' => $matchedItem->id,
                                'inherited_modifier_id' => $modifierId,
                            ]);
                        }
                    }

                    if ($matchedItem && !in_array($matchedItem->id, $matchedExistingIds)) {
                        // Item exists in the order - track it
                        $matchedExistingIds[] = $matchedItem->id;
                        $requestedQty = $itemData['quantity'];
                        $currentQty = $matchedItem->quantity;

                        // Handle quantity = 0 or removal
                        if ($requestedQty <= 0) {
                            if ($matchedItem->status !== 'pending') {
                                $matchedItem->update([
                                    'status' => 'cancelled',
                                    'quantity' => 0,
                                    'subtotal' => 0
                                ]);
                            } else {
                                $matchedItem->delete();
                            }
                            continue;
                        }

                        if ($requestedQty != $currentQty) {
                            // Update the existing order item quantity
                            $matchedItem->update([
                                'quantity' => $requestedQty,
                                'subtotal' => $itemData['price'] * $requestedQty
                            ]);

                            // If increased, send only the DIFFERENCE to KOT
                            if ($requestedQty > $currentQty) {
                                $itemsToProcess[] = [
                                    'data' => array_merge($itemData, ['quantity' => $requestedQty - $currentQty]),
                                    'is_new' => false,
                                    'order_item_id' => $matchedItem->id
                                ];
                                Log::info('Quantity increased, sending difference to KOT', [
                                    'item_id' => $matchedItem->id,
                                    'old_qty' => $currentQty,
                                    'new_qty' => $requestedQty,
                                    'kot_qty' => $requestedQty - $currentQty,
                                ]);
                            }
                            // If decreased, update but don't send to KOT
                        }
                        // If quantity is the same, do nothing (item unchanged, no KOT needed)
                    } else {
                        // Completely new item - will create new order_item and send to KOT
                        $itemsToProcess[] = ['data' => $itemData, 'is_new' => true];
                        Log::info('New item to add', [
                            'item_id' => $itemData['item_id'],
                            'modifier_id' => $modifierId,
                            'name' => $itemData['name'] ?? '',
                            'quantity' => $itemData['quantity'],
                        ]);
                    }
                }

                // Handle items that were in the existing order but NOT in the current request
                // These items have been removed by the user
                foreach ($existingActiveItems as $existingItem) {
                    if (!in_array($existingItem->id, $matchedExistingIds)) {
                        Log::info('Item removed from order', [
                            'item_id' => $existingItem->item_id,
                            'modifier_id' => $existingItem->item_modifier_id,
                            'name' => $existingItem->item_display_name,
                            'status' => $existingItem->status,
                        ]);
                        if ($existingItem->status !== 'pending') {
                            $existingItem->update([
                                'status' => 'cancelled',
                                'quantity' => 0,
                                'subtotal' => 0
                            ]);
                        } else {
                            $existingItem->update(['status' => 'deleted']);
                        }
                    }
                }

                // Update placed_at timestamp
                $order->update(['placed_at' => now()]);
            }

            // Process items and generate KOT - ONLY for new/changed items
            $kotItems = [];

            foreach ($itemsToProcess as $processItem) {
                $itemData = $processItem['data'];
                $item = Item::with('category')->findOrFail($itemData['item_id']);

                if ($processItem['is_new']) {
                    // Create new order item with modifier_id for ID-based stock deduction
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'item_modifier_id' => $itemData['modifier_id'] ?? null, // Store modifier ID for stock deduction
                        'item_display_name' => $itemData['name'] ?? $item->name,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['price'],
                        'subtotal' => $itemData['price'] * $itemData['quantity'],
                    ]);
                } else {
                    // Updated item - use existing order_item
                    $orderItem = OrderItem::find($processItem['order_item_id']);
                }

                // Add to KOT - ONLY new/changed items go here
                $kotItems[] = [
                    'item' => $item,
                    'order_item' => $orderItem,
                    'quantity' => $itemData['quantity'], // Quantity for KOT (difference for updates, full for new)
                ];
            }

            // Recalculate order totals from all active order items
            $order->refresh();
            $subtotalFromAllItems = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->sum('subtotal');

            $order->update([
                'subtotal' => $subtotalFromAllItems,
                'total_amount' => $subtotalFromAllItems,
            ]);

            // Generate KOT/BOT for new/updated items ONLY
            $kotNumbers = ['kot_number' => null, 'bot_number' => null];
            if (!empty($kotItems)) {
                Log::info('Generating KOT/BOT for ' . count($kotItems) . ' new/changed items', [
                    'items' => array_map(function ($ki) {
                        return [
                            'item_id' => $ki['item']->id,
                            'name' => $ki['order_item']->item_display_name ?? $ki['item']->name,
                            'quantity' => $ki['quantity'],
                        ];
                    }, $kotItems),
                ]);
                $kotNumbers = $this->generateKOT($order, $kotItems);
            } else {
                Log::info('No new/changed items - skipping KOT/BOT generation');
            }

            // Prepare items payload for frontend printing (non-cancelled, non-deleted)
            $printItemsRaw = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->with(['item', 'modifiers.modifier'])
                ->get();

            $printItems = $printItemsRaw->map(function ($orderItem) {
                $modifiers = $orderItem->modifiers->map(function ($mod) {
                    return [
                        'id' => $mod->id,
                        'modifier_id' => $mod->modifier_id,
                        'name' => $mod->modifier->name ?? 'Modifier',
                        'price_adjustment' => $mod->price_adjustment,
                    ];
                })->toArray();

                return [
                    'id' => $orderItem->id,
                    'item_id' => $orderItem->item_id,
                    'name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                    'item_name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                    'item_code' => $orderItem->item->item_code ?? '',
                    'unit_price' => $orderItem->unit_price,
                    'price' => $orderItem->unit_price,
                    'quantity' => $orderItem->quantity,
                    'subtotal' => $orderItem->subtotal,
                    'status' => $orderItem->status,
                    'modifiers' => $modifiers,
                    'item' => [
                        'name' => $orderItem->item->name ?? 'Unknown Item',
                        'item_code' => $orderItem->item->item_code ?? '',
                    ],
                ];
            })->values();

            // Attach relations/attributes so JS can read order.orderItems / order_items
            $order = $order->load(['table', 'waiter', 'payment']);
            $order->setRelation('orderItems', $printItemsRaw);
            $order->setAttribute('order_items', $printItems);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isNewOrder ? 'Order placed successfully' : 'Order updated successfully',
                'order' => $order,
                'order_items' => $printItems,
                'orderItems' => $printItems,
                'items' => $printItems,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'table_number' => $order->table ? $order->table->table_number : null,
                'pickme_ref_number' => $order->pickme_ref_number,
                'kot_number' => $kotNumbers['kot_number'],
                'kot_sub_number' => $kotNumbers['kot_sub_number'] ?? null,
                'kot_display_number' => $kotNumbers['kot_display_number'] ?? $kotNumbers['kot_number'],
                'bot_number' => $kotNumbers['bot_number'],
                'bot_sub_number' => $kotNumbers['bot_sub_number'] ?? null,
                'bot_display_number' => $kotNumbers['bot_display_number'] ?? $kotNumbers['bot_number'],
                'kot_items' => $kotNumbers['kot_items'] ?? [],
                'bot_items' => $kotNumbers['bot_items'] ?? [],
                'is_new' => $isNewOrder,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error placing order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate KOT/BOT based on item categories
     * Handles sub-numbering for orders that have items added later:
     * - First KOT/BOT for an order: sub_number = 0 (primary)
     * - Subsequent KOT/BOT additions: sub_number = 1, 2, 3... (secondary)
     */
    private function generateKOT($order, $kotItems)
    {
        // Group items by category - BEVERAGES and DESSERTS go to BOT, everything else to KOT
        $kitchenItems = [];
        $barItems = [];

        foreach ($kotItems as $kotItem) {
            $item = $kotItem['item'];

            // Load category if not already loaded
            if (!$item->relationLoaded('category')) {
                $item->load('category');
            }

            // Check by category_id or category slug - Only BEVERAGES (ID 21) go to BOT
            // Desserts and all other categories go to KOT (Kitchen Order Ticket)
            $isBarItem = false;

            if ($item->category_id === 21) {
                // Only Beverages (ID 21) go to BOT
                $isBarItem = true;
            } elseif ($item->category) {
                // Fallback: check by slug/name for flexibility
                $categorySlug = strtolower($item->category->slug);
                $categoryName = strtoupper($item->category->name);
                $isBarItem = (
                    $categorySlug === 'beverages' || $categoryName === 'BEVERAGES'
                );
            }

            if ($isBarItem) {
                $barItems[] = $kotItem;
            } else {
                $kitchenItems[] = $kotItem;
            }
        }

        // Create KOT for kitchen
        $kotNumber = null;
        $kotSubNumber = null;
        $kotDisplayNumber = null;
        if (!empty($kitchenItems)) {
            // Get the next sub-number for this order's kitchen station KOTs
            $subNumber = Kot::getNextSubNumber($order->id, 1); // 1 = kitchen station

            $kot = Kot::create([
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'waiter_id' => $order->waiter_id,
                'kitchen_station_id' => 1, // Default kitchen station
                'sub_number' => $subNumber,
                'status' => 'pending',
                'printed_at' => now(),
                'print_count' => 1,
            ]);

            $kotNumber = $kot->kot_number;
            $kotSubNumber = $subNumber;
            $kotDisplayNumber = $kot->getDisplayNumber();

            foreach ($kitchenItems as $kotItem) {
                KotItem::create([
                    'kot_id' => $kot->id,
                    'order_item_id' => $kotItem['order_item']->id,
                    'item_name' => $kotItem['order_item']->item_display_name ?? $kotItem['item']->name,
                    'quantity' => $kotItem['quantity'],
                    'special_instructions' => $kotItem['order_item']->special_instructions,
                    'modifiers' => $kotItem['order_item']->modifiers ?? null,
                    'status' => 'pending',
                ]);
            }
        }

        // Create BOT for bar (using same KOT structure)
        $botNumber = null;
        $botSubNumber = null;
        $botDisplayNumber = null;
        if (!empty($barItems)) {
            // Get the next sub-number for this order's bar station BOTs
            $subNumber = Kot::getNextSubNumber($order->id, 2); // 2 = bar station

            $bot = Kot::create([
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'waiter_id' => $order->waiter_id,
                'kitchen_station_id' => 2, // Bar station
                'sub_number' => $subNumber,
                'status' => 'pending',
                'printed_at' => now(),
                'print_count' => 1,
            ]);

            $botNumber = $bot->kot_number;
            $botSubNumber = $subNumber;
            $botDisplayNumber = $bot->getDisplayNumber();

            foreach ($barItems as $botItem) {
                KotItem::create([
                    'kot_id' => $bot->id,
                    'order_item_id' => $botItem['order_item']->id,
                    'item_name' => $botItem['order_item']->item_display_name ?? $botItem['item']->name,
                    'quantity' => $botItem['quantity'],
                    'special_instructions' => $botItem['order_item']->special_instructions,
                    'modifiers' => $botItem['order_item']->modifiers ?? null,
                    'status' => 'pending',
                ]);
            }
        }

        // Update order print count
        $order->increment('kot_print_count');
        $order->last_kot_printed_at = now();
        $order->save();

        // Prepare items data for frontend printing
        $kotItemsData = array_map(function ($kotItem) {
            return [
                'name' => $kotItem['order_item']->item_display_name ?? $kotItem['item']->name,
                'quantity' => $kotItem['quantity'],
                'item_id' => $kotItem['item']->id
            ];
        }, $kitchenItems);

        $botItemsData = array_map(function ($botItem) {
            return [
                'name' => $botItem['order_item']->item_display_name ?? $botItem['item']->name,
                'quantity' => $botItem['quantity'],
                'item_id' => $botItem['item']->id
            ];
        }, $barItems);

        return [
            'kot_number' => $kotNumber,
            'kot_sub_number' => $kotSubNumber,
            'kot_display_number' => $kotDisplayNumber,
            'bot_number' => $botNumber,
            'bot_sub_number' => $botSubNumber,
            'bot_display_number' => $botDisplayNumber,
            'kot_items' => $kotItemsData,
            'bot_items' => $botItemsData
        ];
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:cash,card,credit,card_cash,mixed',
            'amount_paid' => 'nullable|numeric|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::findOrFail($validated['order_id']);

            // Check if order is already paid
            if ($order->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid'
                ], 400);
            }

            $totalAmount = $order->total_amount;

            // Validate payment amounts based on method
            if ($validated['payment_method'] === 'card_cash' || $validated['payment_method'] === 'mixed') {
                $cashAmt = $validated['cash_amount'] ?? 0;
                $cardAmt = $validated['card_amount'] ?? 0;
                if (($cashAmt + $cardAmt) < $totalAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient payment amount'
                    ], 400);
                }
            } elseif ($validated['payment_method'] === 'cash') {
                if (($validated['cash_amount'] ?? $validated['amount_paid'] ?? 0) < $totalAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient cash amount'
                    ], 400);
                }
            }

            // Map payment methods
            $paymentMethodMap = [
                'cash' => 'cash',
                'card' => 'card',
                'credit' => 'credit',
                'card_cash' => 'mixed',
                'mixed' => 'mixed'
            ];

            // Calculate specific payment amounts
            $cashAmount = 0;
            $cardAmount = 0;
            $creditAmount = 0;
            $changeAmount = 0;

            switch ($validated['payment_method']) {
                case 'cash':
                    $cashAmount = $validated['cash_amount'] ?? $validated['amount_paid'] ?? 0;
                    $changeAmount = max(0, $cashAmount - $totalAmount);
                    break;
                case 'card':
                    $cardAmount = $validated['card_amount'] ?? $validated['amount_paid'] ?? $totalAmount;
                    break;
                case 'credit':
                    $creditAmount = $totalAmount;
                    break;
                case 'card_cash':
                case 'mixed':
                    // For mixed payments: store full amounts given
                    $cashAmount = $validated['cash_amount'] ?? 0;
                    $cardAmount = $validated['card_amount'] ?? 0;

                    // Card is applied first (exact, no change), cash pays the rest
                    $remainingAfterCard = $totalAmount - $cardAmount;
                    // Change only comes from excess cash (cash given minus what's needed after card)
                    $changeAmount = max(0, $cashAmount - max(0, $remainingAfterCard));
                    break;
            }

            // Generate unique payment number
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);

            // Create payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_number' => $paymentNumber,
                'total_amount' => $totalAmount,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'credit_amount' => $creditAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethodMap[$validated['payment_method']],
                'payment_status' => 'completed',
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            // Create payment splits for mixed payments
            if (($validated['payment_method'] === 'card_cash' || $validated['payment_method'] === 'mixed') && ($cashAmount > 0 || $cardAmount > 0)) {
                if ($cashAmount > 0) {
                    \App\Models\PaymentSplit::create([
                        'payment_id' => $payment->id,
                        'payment_method' => 'cash',
                        'amount' => $cashAmount,
                    ]);
                }
                if ($cardAmount > 0) {
                    \App\Models\PaymentSplit::create([
                        'payment_id' => $payment->id,
                        'payment_method' => 'card',
                        'amount' => $cardAmount,
                    ]);
                }
            }

            // Update order
            $oldStatus = $order->status;
            $order->update([
                'status' => 'completed',
                'is_paid' => true,
                'completed_at' => now(),
            ]);

            // Log payment and status change
            OrderLog::logPaymentAdded($order, [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'payment_method' => $payment->payment_method,
                'total_amount' => $payment->total_amount,
                'cash_amount' => $payment->cash_amount,
                'card_amount' => $payment->card_amount,
                'change_amount' => $payment->change_amount,
            ], 'Payment processed');

            // Log status change
            OrderLog::logStatusChanged($order, $oldStatus, 'completed', 'Order completed after payment');

            // Free up table if dine-in
            if ($order->table_id) {
                $table = Table::find($order->table_id);
                if ($table) {
                    $table->update([
                        'status' => 'available',
                        'current_order_id' => null,
                    ]);
                }
            }

            // Deduct stock for Finished Goods items from CashierSubStock (FG Stock)
            // Stock is deducted even if not present (creates stock entry with negative quantity)
            $orderItemsForStock = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->with(['item.category'])
                ->get();

            foreach ($orderItemsForStock as $orderItem) {
                if (!$orderItem->item)
                    continue;

                // Check if item should be deducted as Finished Good
                // BOTH conditions must be true: is_finished_goods AND is_stock_count
                // No fallback by category - only explicit checkbox settings matter
                $isFinishedGoodsItem = $orderItem->item->is_finished_goods && $orderItem->item->is_stock_count;

                // Deduct stock ONLY if item has BOTH Finished Goods AND Stock Count checked
                if ($isFinishedGoodsItem && $orderItem->quantity > 0) {
                    // Use ID-based matching (modifier_id stored directly on order_item)
                    $modifierId = $orderItem->item_modifier_id;

                    // Try ID-based matching first (most reliable - uses stored modifier_id)
                    $result = CashierSubStock::deductForSaleById(
                        $orderItem->item_id,
                        $modifierId,
                        $orderItem->quantity,
                        Auth::id()
                    );

                    // Fallback to name-based matching (for legacy orders without item_modifier_id)
                    if (!$result && !$modifierId) {
                        $displayName = $orderItem->item_display_name ?? $orderItem->item->name;
                        CashierSubStock::deductForSaleByDisplayName(
                            $orderItem->item_id,
                            $displayName,
                            $orderItem->quantity,
                            Auth::id()
                        );
                    }
                }
            }

            // Deduct raw materials for NON-Finished Goods items based on recipe
            foreach ($orderItemsForStock as $orderItem) {
                if (!$orderItem->item)
                    continue;

                // Only process non-finished goods items that have stock count enabled
                if (!$orderItem->item->is_finished_goods && $orderItem->item->is_stock_count && $orderItem->quantity > 0) {
                    // Use ID-based matching (modifier_id stored directly on order_item)
                    $modifierId = $orderItem->item_modifier_id;

                    // Get recipes - portion-specific if modifier exists
                    $recipes = ItemRecipe::where('item_id', $orderItem->item_id)
                        ->where('item_modifier_id', $modifierId)
                        ->get();

                    // Fallback to item-level recipes if no portion-specific recipes found
                    if ($recipes->isEmpty() && $modifierId) {
                        $recipes = ItemRecipe::where('item_id', $orderItem->item_id)
                            ->whereNull('item_modifier_id')
                            ->get();
                    }

                    // If no modifier, get item-level recipes directly
                    if ($recipes->isEmpty() && !$modifierId) {
                        $recipes = ItemRecipe::where('item_id', $orderItem->item_id)
                            ->whereNull('item_modifier_id')
                            ->get();
                    }

                    // Deduct each raw material from CashierSubStock
                    foreach ($recipes as $recipe) {
                        $totalQuantity = $recipe->quantity * $orderItem->quantity;
                        $subStock = CashierSubStock::getOrCreateForItem($recipe->main_stock_item_id);
                        $subStock->deductStockForSale($totalQuantity, Auth::id());
                    }
                }
            }

            // Prepare items payload for receipt/printing
            $printItemsRaw = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->with(['item', 'modifiers.modifier'])
                ->get();

            $printItems = $printItemsRaw->map(function ($orderItem) {
                $modifiers = $orderItem->modifiers->map(function ($mod) {
                    return [
                        'id' => $mod->id,
                        'modifier_id' => $mod->modifier_id,
                        'name' => $mod->modifier->name ?? 'Modifier',
                        'price_adjustment' => $mod->price_adjustment,
                    ];
                })->toArray();

                return [
                    'id' => $orderItem->id,
                    'item_id' => $orderItem->item_id,
                    'name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                    'item_name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                    'item_code' => $orderItem->item->item_code ?? '',
                    'unit_price' => $orderItem->unit_price,
                    'price' => $orderItem->unit_price,
                    'quantity' => $orderItem->quantity,
                    'subtotal' => $orderItem->subtotal,
                    'status' => $orderItem->status,
                    'modifiers' => $modifiers,
                    'item' => [
                        'name' => $orderItem->item->name ?? 'Unknown Item',
                        'item_code' => $orderItem->item->item_code ?? '',
                    ],
                ];
            })->values();

            $order = $order->load(['payment', 'table', 'waiter']);
            $order->setRelation('orderItems', $printItemsRaw);
            $order->setAttribute('order_items', $printItems);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'order' => $order,
                'order_items' => $printItems,
                'orderItems' => $printItems,
                'items' => $printItems,
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print receipt.
     */
    public function printReceipt($orderId)
    {
        $order = Order::with([
            'orderItems' => function ($query) {
                $query->where('status', '!=', 'deleted')
                    ->orderBy('id', 'asc')  // Ensure consistent ordering
                    ->with(['item', 'modifiers.modifier']);
                // NO LIMIT - fetch ALL items for printing
            },
            'table',
            'waiter',
            'payment'
        ])->findOrFail($orderId);

        return view('pos.receipt', compact('order'));
    }

    /**
     * Verify supervisor PIN for void operations
     * PIN is dynamically generated and changes every 5 minutes
     */
    public function verifySupervisorPin(Request $request)
    {
        $validated = $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        // Find all supervisors and check their dynamic PIN
        $supervisors = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'supervisor');
        })
            ->where('is_active', true)
            ->get();

        foreach ($supervisors as $supervisor) {
            // Check against the dynamically generated PIN
            if ($supervisor->dynamic_pin === $validated['pin']) {
                return response()->json([
                    'success' => true,
                    'message' => 'PIN verified successfully',
                    'supervisor_name' => $supervisor->name
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid supervisor PIN'
        ], 401);
    }

    /**
     * Void items from an order (reduce quantity)
     */
    public function voidItems(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'void_items' => 'required|array|min:1',
            'void_items.*.item_id' => 'required|exists:items,id',
            'void_items.*.modifier_id' => 'nullable|exists:item_modifiers,id', // For ID-based matching
            'void_items.*.item_name' => 'required|string',
            'void_items.*.void_quantity' => 'required|integer|min:1',
            'supervisor_pin' => 'required|string|size:4',
        ]);

        // Verify supervisor PIN again using dynamic PIN
        $supervisors = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'supervisor');
        })
            ->where('is_active', true)
            ->get();

        $supervisor = null;
        foreach ($supervisors as $sup) {
            if ($sup->dynamic_pin === $validated['supervisor_pin']) {
                $supervisor = $sup;
                break;
            }
        }

        if (!$supervisor) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid supervisor PIN'
            ], 401);
        }

        DB::beginTransaction();
        try {
            $order = Order::with('orderItems.item')->findOrFail($validated['order_id']);

            $voidedItems = [];
            $cancelKotItems = []; // Items to print on cancel KOT

            foreach ($validated['void_items'] as $voidItem) {
                // Find the matching order item using ID-based matching (item_id + modifier_id)
                $modifierId = $voidItem['modifier_id'] ?? null;
                $orderItem = $order->orderItems->filter(function ($item) use ($voidItem, $modifierId) {
                    // Match by item_id
                    if ($item->item_id != $voidItem['item_id']) {
                        return false;
                    }
                    // Match by modifier_id (ID-based matching - most reliable)
                    if ($modifierId !== null) {
                        if ($item->item_modifier_id != $modifierId) {
                            return false;
                        }
                    } else {
                        // Fallback to name matching for legacy orders without modifier_id
                        $itemName = $item->item_display_name ?? ($item->item->name ?? '');
                        if ($itemName !== $voidItem['item_name']) {
                            return false;
                        }
                    }
                    // Exclude cancelled/deleted items
                    if (in_array($item->status, ['cancelled', 'deleted'])) {
                        return false;
                    }
                    return true;
                })->first();

                if (!$orderItem) {
                    continue; // Skip if item not found
                }

                $currentQty = $orderItem->quantity;
                $voidQty = min($voidItem['void_quantity'], $currentQty);
                $newQty = $currentQty - $voidQty;
                $unitPrice = $orderItem->unit_price;
                $voidedAmount = $voidQty * $unitPrice;

                if ($newQty <= 0) {
                    // Mark as cancelled
                    $orderItem->update([
                        'status' => 'cancelled',
                        'quantity' => 0,
                        'subtotal' => 0
                    ]);
                } else {
                    // Reduce quantity
                    $orderItem->update([
                        'quantity' => $newQty,
                        'subtotal' => $unitPrice * $newQty
                    ]);
                }

                // Create void record for audit trail
                $voidRecord = VoidRecord::create([
                    'void_number' => VoidRecord::generateVoidNumber(),
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'item_id' => $voidItem['item_id'],
                    'order_item_id' => $orderItem->id,
                    'item_name' => $voidItem['item_name'],
                    'item_code' => $orderItem->item->item_code ?? null,
                    'voided_quantity' => $voidQty,
                    'unit_price' => $unitPrice,
                    'voided_amount' => $voidedAmount,
                    'cashier_id' => Auth::id(),
                    'supervisor_id' => $supervisor->id,
                    'supervisor_name' => $supervisor->name,
                    'reason' => $request->input('reason'), // Optional reason from frontend
                    'table_number' => $order->table?->table_number,
                    'cancel_kot_number' => null, // Will be updated after KOT creation
                ]);

                $voidedItems[] = [
                    'item_id' => $voidItem['item_id'],
                    'item_name' => $voidItem['item_name'],
                    'voided_quantity' => $voidQty,
                    'new_quantity' => $newQty,
                    'voided_amount' => $voidedAmount,
                    'void_number' => $voidRecord->void_number,
                ];

                // Add to cancel KOT items
                $cancelKotItems[] = [
                    'name' => $voidItem['item_name'],
                    'quantity' => $voidQty,
                    'item_id' => $voidItem['item_id'],
                    'is_cancelled' => true,
                    'void_record_id' => $voidRecord->id,
                ];
            }

            // Recalculate order totals
            $order->refresh();
            $subtotalFromAllItems = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->sum('subtotal');

            $order->update([
                'subtotal' => $subtotalFromAllItems,
                'total_amount' => $subtotalFromAllItems,
            ]);

            // Create a Cancel KOT for voided items
            $cancelKotNumber = null;
            $cancelBotNumber = null;

            if (!empty($cancelKotItems)) {
                // Separate kitchen and bar items
                $kitchenCancelItems = [];
                $barCancelItems = [];

                foreach ($cancelKotItems as $cancelItem) {
                    $item = Item::with('category')->find($cancelItem['item_id']);
                    if ($item) {
                        // Only Beverages (ID 21) go to Cancel BOT
                        // Desserts and all other categories go to Cancel KOT
                        $isBarItem = false;
                        if ($item->category_id === 21) {
                            $isBarItem = true;
                        } elseif ($item->category) {
                            // Fallback: check by slug/name for flexibility
                            $categorySlug = strtolower($item->category->slug);
                            $categoryName = strtoupper($item->category->name);
                            $isBarItem = (
                                $categorySlug === 'beverages' || $categoryName === 'BEVERAGES'
                            );
                        }

                        if ($isBarItem) {
                            $barCancelItems[] = $cancelItem;
                        } else {
                            $kitchenCancelItems[] = $cancelItem;
                        }
                    }
                }

                // Create Cancel KOT for kitchen items
                if (!empty($kitchenCancelItems)) {
                    $cancelKot = Kot::create([
                        'order_id' => $order->id,
                        'table_id' => $order->table_id,
                        'waiter_id' => Auth::id(),
                        'kitchen_station_id' => 1,
                        'status' => 'cancelled',
                        'printed_at' => now(),
                        'print_count' => 1,
                    ]);
                    // Use getDisplayNumber() to include sub-number if applicable
                    $cancelKotNumber = 'CANCEL-' . $cancelKot->getDisplayNumber();

                    // Update void records with cancel KOT number
                    foreach ($kitchenCancelItems as $cancelItem) {
                        if (isset($cancelItem['void_record_id'])) {
                            VoidRecord::where('id', $cancelItem['void_record_id'])
                                ->update(['cancel_kot_number' => $cancelKotNumber]);
                        }
                    }
                }

                // Create Cancel BOT for bar items
                if (!empty($barCancelItems)) {
                    $cancelBot = Kot::create([
                        'order_id' => $order->id,
                        'table_id' => $order->table_id,
                        'waiter_id' => Auth::id(),
                        'kitchen_station_id' => 2,
                        'status' => 'cancelled',
                        'printed_at' => now(),
                        'print_count' => 1,
                    ]);
                    // Use getDisplayNumber() to include sub-number if applicable
                    $cancelBotNumber = 'CANCEL-' . $cancelBot->getDisplayNumber();

                    // Update void records with cancel BOT number
                    foreach ($barCancelItems as $cancelItem) {
                        if (isset($cancelItem['void_record_id'])) {
                            VoidRecord::where('id', $cancelItem['void_record_id'])
                                ->update(['cancel_kot_number' => $cancelBotNumber]);
                        }
                    }
                }
            }

            // Load updated items for frontend
            $updatedItems = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->with('item')
                ->get()
                ->map(function ($orderItem) {
                    return [
                        'item_id' => $orderItem->item_id,
                        'name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                        'price' => $orderItem->unit_price,
                        'quantity' => $orderItem->quantity,
                        'subtotal' => $orderItem->subtotal,
                    ];
                })->values();

            // Check if all items have been voided (no active items remaining)
            // We don't cancel immediately - user can still add new items
            // Order will be cancelled only when user starts a new order without adding items
            $allItemsVoided = $updatedItems->isEmpty();

            // Log the void operation
            OrderLog::logAction($order, OrderLog::ACTION_ITEM_REMOVED, [
                'reason' => $request->input('reason') ?? 'Items voided by supervisor: ' . $supervisor->name,
                'old_values' => [
                    'voided_items' => $voidedItems,
                    'supervisor_id' => $supervisor->id,
                    'supervisor_name' => $supervisor->name,
                ],
                'description' => count($voidedItems) . ' item(s) voided by supervisor ' . $supervisor->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $allItemsVoided ? 'All items voided - Add new items or start a new order' : 'Items voided successfully',
                'voided_items' => $voidedItems,
                'updated_items' => $updatedItems,
                'new_total' => $order->total_amount,
                'cancel_kot_number' => $cancelKotNumber,
                'cancel_bot_number' => $cancelBotNumber,
                'cancel_kot_items' => $kitchenCancelItems ?? [],
                'cancel_bot_items' => $barCancelItems ?? [],
                'supervisor_name' => $supervisor->name,
                'all_items_voided' => $allItemsVoided,
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error voiding items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a voided order when user starts a new order without adding items
     * This is called when user had voided all items and then starts a fresh order
     */
    public function cancelVoidedOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('orderItems')->findOrFail($validated['order_id']);

            // Check if order has any active items
            $activeItemsCount = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->count();

            if ($activeItemsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order still has active items. Cannot cancel.'
                ], 400);
            }

            // Cancel the order
            $oldStatus = $order->status;
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => 'All items voided - New order started',
            ]);

            // Log the cancellation
            OrderLog::logStatusChanged($order, $oldStatus, 'cancelled', 'All items voided - New order started');
            OrderLog::logDeleted($order, 'Order cancelled after all items were voided');

            // Free up the table if dine-in
            if ($order->table_id) {
                $table = Table::find($order->table_id);
                if ($table) {
                    $table->update([
                        'status' => 'available',
                        'current_order_id' => null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voided order cancelled successfully',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling voided order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer an order to a different table
     */
    public function transferTable(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'new_table_id' => 'required|exists:tables,id',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('table')->findOrFail($validated['order_id']);

            // Validate order type
            if ($order->order_type !== 'dine_in') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only dine-in orders can be transferred'
                ], 400);
            }

            // Validate order status
            if ($order->is_paid || $order->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot transfer a completed or paid order'
                ], 400);
            }

            // Get the old and new tables
            $oldTable = Table::find($order->table_id);
            $newTable = Table::findOrFail($validated['new_table_id']);

            // Check if new table is available
            if ($newTable->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected table is not available'
                ], 400);
            }

            // Check if trying to transfer to the same table
            if ($order->table_id === $validated['new_table_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already on this table'
                ], 400);
            }

            // Free up the old table
            if ($oldTable) {
                $oldTable->update([
                    'status' => 'available',
                    'current_order_id' => null,
                ]);
            }

            // Reserve the new table
            $newTable->update([
                'status' => 'ordered',
                'current_order_id' => $order->id,
            ]);

            // Update the order's table
            $oldTableId = $order->table_id;
            $order->update([
                'table_id' => $validated['new_table_id'],
            ]);

            // Log the table transfer
            OrderLog::logTableChanged(
                $order,
                $oldTableId,
                $validated['new_table_id'],
                'Order transferred from ' . ($oldTable ? $oldTable->table_number : 'N/A') . ' to ' . $newTable->table_number
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Table transferred successfully',
                'order' => $order->load('table'),
                'old_table' => $oldTable ? $oldTable->table_number : null,
                'new_table' => $newTable->table_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error transferring table: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Merge two orders - move items from source order to target order
     */
    public function mergeOrder(Request $request)
    {
        $validated = $request->validate([
            'target_order_id' => 'required|exists:orders,id',
            'source_order_id' => 'required|exists:orders,id',
        ]);

        // Prevent merging the same order
        if ($validated['target_order_id'] === $validated['source_order_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot merge an order with itself'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $targetOrder = Order::with('orderItems.item')->findOrFail($validated['target_order_id']);
            $sourceOrder = Order::with('orderItems.item')->findOrFail($validated['source_order_id']);

            // Validate both orders are pending and not paid
            if ($targetOrder->is_paid || $targetOrder->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot merge into a completed or paid order'
                ], 400);
            }

            if ($sourceOrder->is_paid || $sourceOrder->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot merge a completed or paid order'
                ], 400);
            }

            // Get active items from source order
            $sourceItems = $sourceOrder->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->get();

            if ($sourceItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Source order has no active items to merge'
                ], 400);
            }

            // Move items from source to target
            foreach ($sourceItems as $sourceItem) {
                // Check if similar item already exists in target order (same item_id and modifier_id)
                $existingItem = $targetOrder->orderItems()
                    ->where('item_id', $sourceItem->item_id)
                    ->where('item_modifier_id', $sourceItem->item_modifier_id)
                    ->where('item_display_name', $sourceItem->item_display_name)
                    ->whereNotIn('status', ['cancelled', 'deleted'])
                    ->first();

                if ($existingItem) {
                    // Merge quantities for existing item
                    $newQuantity = $existingItem->quantity + $sourceItem->quantity;
                    $newSubtotal = $existingItem->unit_price * $newQuantity;

                    $existingItem->update([
                        'quantity' => $newQuantity,
                        'subtotal' => $newSubtotal
                    ]);

                    // Mark source item as merged/cancelled
                    $sourceItem->update([
                        'status' => 'cancelled',
                        'quantity' => 0,
                        'subtotal' => 0
                    ]);
                } else {
                    // Move item to target order
                    $sourceItem->update([
                        'order_id' => $targetOrder->id
                    ]);
                }
            }

            // Recalculate target order totals
            $targetOrder->refresh();
            $subtotalFromAllItems = $targetOrder->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->sum('subtotal');

            $targetOrder->update([
                'subtotal' => $subtotalFromAllItems,
                'total_amount' => $subtotalFromAllItems,
            ]);

            // Cancel the source order
            $sourceOrder->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => 'Merged into Order #' . $targetOrder->order_number,
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            // Free up the source table if dine-in
            if ($sourceOrder->table_id) {
                $sourceTable = Table::find($sourceOrder->table_id);
                if ($sourceTable) {
                    $sourceTable->update([
                        'status' => 'available',
                        'current_order_id' => null,
                    ]);
                }
            }

            // Log the merge operation on target order
            OrderLog::logMerged($targetOrder, $sourceOrder->id, 'Order #' . $sourceOrder->order_number . ' merged into this order');

            // Log the cancellation on source order
            OrderLog::logStatusChanged($sourceOrder, 'pending', 'cancelled', 'Merged into Order #' . $targetOrder->order_number);
            OrderLog::logDeleted($sourceOrder, 'Order cancelled after being merged into Order #' . $targetOrder->order_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order #' . $sourceOrder->order_number . ' merged into Order #' . $targetOrder->order_number . ' successfully',
                'target_order' => $targetOrder->load('orderItems.item'),
                'new_total' => $targetOrder->total_amount,
                'merged_items_count' => $sourceItems->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error merging orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order activity logs
     */
    public function getOrderLogs($orderId)
    {
        try {
            $order = Order::with(['logs.performedBy'])->findOrFail($orderId);

            $logs = $order->logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => $log->action_label,
                    'action_color' => $log->action_color,
                    'old_status' => $log->old_status,
                    'new_status' => $log->new_status,
                    'reason' => $log->reason,
                    'description' => $log->description,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'performed_by' => $log->performedBy ? [
                        'id' => $log->performedBy->id,
                        'name' => $log->performedBy->name,
                    ] : null,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->format('M d, Y h:i:s A'),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            });

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'logs' => $logs,
                'total_logs' => $logs->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order logs: ' . $e->getMessage()
            ], 500);
        }
    }
}
