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
use App\Models\RestaurantStock;
use App\Models\VoidRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class POSController extends Controller
{
    /**
     * Display the POS interface.
     */
    public function index()
    {
        $categories = Category::active()
            ->ordered()
            ->with(['availableItems' => function ($query) {
                $query->available()->orderBy('display_order')
                    ->with(['modifiers.itemPrices', 'itemPrices']);
            }])
            ->get();

        $tables = Table::orderByRaw("CAST(SUBSTRING(table_number, 2) AS UNSIGNED)")->get();

        return view('pos.index', compact('categories', 'tables'));
    }

    /**
     * Get item details for POS.
     */
    public function getItem($id)
    {
        $item = Item::with(['modifiers' => function ($query) {
            $query->where('is_active', true);
        }])->findOrFail($id);

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
                    ->with(['item', 'modifiers.modifier']);
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

                // Get existing order items indexed by item_id + display name
                $existingItems = $order->orderItems->keyBy(function ($item) {
                    return $item->item_id . '_' . ($item->item_display_name ?? $item->item->name);
                });

                $itemsToProcess = [];
                $processedKeys = []; // Track which items are still in the order

                // Process each item from the request
                foreach ($validated['items'] as $itemData) {
                    $key = $itemData['item_id'] . '_' . $itemData['name'];
                    $processedKeys[] = $key;

                    if ($existingItems->has($key)) {
                        // Item exists - update it
                        $existingItem = $existingItems->get($key);
                        $requestedQty = $itemData['quantity'];
                        $currentQty = $existingItem->quantity;

                        // Handle quantity = 0 or removal
                        if ($requestedQty <= 0) {
                            // Mark item as cancelled or delete it
                            if ($existingItem->status !== 'pending') {
                                // If already sent to kitchen, mark as cancelled
                                $existingItem->update([
                                    'status' => 'cancelled',
                                    'quantity' => 0,
                                    'subtotal' => 0
                                ]);
                            } else {
                                // If still pending, delete it
                                $existingItem->delete();
                            }
                            continue;
                        }

                        if ($requestedQty != $currentQty) {
                            // Update the existing order item
                            $existingItem->update([
                                'quantity' => $requestedQty,
                                'subtotal' => $itemData['price'] * $requestedQty
                            ]);

                            // If increased, send difference to KOT
                            if ($requestedQty > $currentQty) {
                                $itemsToProcess[] = [
                                    'data' => array_merge($itemData, ['quantity' => $requestedQty - $currentQty]),
                                    'is_new' => false,
                                    'order_item_id' => $existingItem->id
                                ];
                            }
                        }
                        // If quantity same or decreased, no KOT needed
                    } else {
                        // Completely new item - will create new order_item
                        $itemsToProcess[] = ['data' => $itemData, 'is_new' => true];
                    }
                }

                // Handle items that were removed from the order (not in current request)
                foreach ($existingItems as $key => $existingItem) {
                    if (!in_array($key, $processedKeys)) {
                        // Item was removed from the order
                        if ($existingItem->status !== 'pending') {
                            // If already sent to kitchen, mark as cancelled
                            $existingItem->update([
                                'status' => 'cancelled',
                                'quantity' => 0,
                                'subtotal' => 0
                            ]);
                        } else {
                            // If still pending, mark as deleted
                            $existingItem->update(['status' => 'deleted']);
                        }
                    }
                }

                // Update placed_at timestamp
                $order->update(['placed_at' => now()]);
            }

            // Process items and generate KOT
            $kotItems = [];

            foreach ($itemsToProcess as $processItem) {
                $itemData = $processItem['data'];
                $item = Item::with('category')->findOrFail($itemData['item_id']);

                if ($processItem['is_new']) {
                    // Create new order item
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'item_display_name' => $itemData['name'] ?? $item->name,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['price'],
                        'subtotal' => $itemData['price'] * $itemData['quantity'],
                    ]);
                } else {
                    // Updated item - use existing order_item
                    $orderItem = OrderItem::find($processItem['order_item_id']);
                }

                // Add to KOT
                $kotItems[] = [
                    'item' => $item,
                    'order_item' => $orderItem,
                    'quantity' => $itemData['quantity'], // Quantity for KOT
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

            // Generate KOT/BOT for new/updated items
            $kotNumbers = ['kot_number' => null, 'bot_number' => null];
            if (!empty($kotItems)) {
                $kotNumbers = $this->generateKOT($order, $kotItems);
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
                'bot_number' => $kotNumbers['bot_number'],
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

            // Check by category_id or category slug - BEVERAGES (ID 21) or DESSERTS (ID 20) go to BOT
            // All other categories go to KOT (Kitchen Order Ticket)
            $isBarItem = false;

            if (in_array($item->category_id, [20, 21])) {
                // Dessert (ID 20) and Beverages (ID 21) go to BOT
                $isBarItem = true;
            } elseif ($item->category) {
                // Fallback: check by slug/name for flexibility
                $categorySlug = strtolower($item->category->slug);
                $categoryName = strtoupper($item->category->name);
                $isBarItem = (
                    $categorySlug === 'beverages' || $categoryName === 'BEVERAGES' ||
                    $categorySlug === 'desserts' || $categoryName === 'DESSERTS' ||
                    $categorySlug === 'dessert' || $categoryName === 'DESSERT'
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
        if (!empty($kitchenItems)) {
            $kot = Kot::create([
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'waiter_id' => $order->waiter_id,
                'kitchen_station_id' => 1, // Default kitchen station
                'status' => 'pending',
                'printed_at' => now(),
                'print_count' => 1,
            ]);

            $kotNumber = $kot->kot_number;

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
        if (!empty($barItems)) {
            $bot = Kot::create([
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'waiter_id' => $order->waiter_id,
                'kitchen_station_id' => 2, // Bar station
                'status' => 'pending',
                'printed_at' => now(),
                'print_count' => 1,
            ]);

            $botNumber = $bot->kot_number;

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
            'bot_number' => $botNumber,
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
            $order->update([
                'status' => 'completed',
                'is_paid' => true,
                'completed_at' => now(),
            ]);

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

            // Deduct stock for Finished Goods items from RestaurantStock
            // Stock is deducted even if not present (creates stock entry with negative quantity)
            $orderItemsForStock = $order->orderItems()
                ->whereNotIn('status', ['cancelled', 'deleted'])
                ->with(['item'])
                ->get();

            foreach ($orderItemsForStock as $orderItem) {
                if (!$orderItem->item) continue;

                // Only deduct stock for items marked as "Finished Goods"
                if ($orderItem->item->is_finished_goods && $orderItem->quantity > 0) {
                    // Use display name to find modifier and deduct stock
                    $displayName = $orderItem->item_display_name ?? $orderItem->item->name;
                    RestaurantStock::deductForSaleByDisplayName(
                        $orderItem->item_id,
                        $displayName,
                        $orderItem->quantity,
                        Auth::id()
                    );
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
                    ->with(['item', 'modifiers.modifier']);
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
                // Find the matching order item using filter for complex matching
                $orderItem = $order->orderItems->filter(function ($item) use ($voidItem) {
                    // Match by item_id
                    if ($item->item_id != $voidItem['item_id']) {
                        return false;
                    }
                    // Match by name
                    $itemName = $item->item_display_name ?? ($item->item->name ?? '');
                    if ($itemName !== $voidItem['item_name']) {
                        return false;
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
                        // Dessert (ID 20) and Beverages (ID 21) go to Cancel BOT
                        // All other categories go to Cancel KOT
                        $isBarItem = false;
                        if (in_array($item->category_id, [20, 21])) {
                            $isBarItem = true;
                        } elseif ($item->category) {
                            // Fallback: check by slug/name for flexibility
                            $categorySlug = strtolower($item->category->slug);
                            $categoryName = strtoupper($item->category->name);
                            $isBarItem = (
                                $categorySlug === 'beverages' || $categoryName === 'BEVERAGES' ||
                                $categorySlug === 'desserts' || $categoryName === 'DESSERTS' ||
                                $categorySlug === 'dessert' || $categoryName === 'DESSERT'
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
                    $cancelKotNumber = 'CANCEL-' . $cancelKot->kot_number;

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
                    $cancelBotNumber = 'CANCEL-' . $cancelBot->kot_number;

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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items voided successfully',
                'voided_items' => $voidedItems,
                'updated_items' => $updatedItems,
                'new_total' => $order->total_amount,
                'cancel_kot_number' => $cancelKotNumber,
                'cancel_bot_number' => $cancelBotNumber,
                'cancel_kot_items' => $kitchenCancelItems ?? [],
                'cancel_bot_items' => $barCancelItems ?? [],
                'supervisor_name' => $supervisor->name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error voiding items: ' . $e->getMessage()
            ], 500);
        }
    }
}
