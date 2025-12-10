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
                $query->available()->orderBy('display_order')->with('modifiers');
            }])
            ->get();

        $tables = Table::orderBy('table_number')->get();

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
            ->orderBy('table_number')
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
        $openOrders = Order::with(['table', 'orderItems.item'])
            ->where('status', 'pending')
            ->where('is_paid', false)
            ->orderBy('created_at', 'desc')
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

        $closedOrders = Order::with(['table', 'orderItems.item', 'payment'])
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
        $order = Order::with(['orderItems.item', 'table', 'waiter', 'payment'])
            ->findOrFail($orderId);

        // Transform orderItems to items format expected by frontend
        // Only include non-cancelled items
        $items = $order->orderItems
            ->filter(function ($orderItem) {
                return $orderItem->status !== 'cancelled';
            })
            ->map(function ($orderItem) {
                return [
                    'item_id' => $orderItem->item_id,
                    'name' => $orderItem->item_display_name ?? $orderItem->item->name ?? 'Unknown Item',
                    'price' => $orderItem->unit_price,
                    'quantity' => $orderItem->quantity,
                    'modifiers' => []
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
                'items' => $items,
                'total_amount' => $order->total_amount,
                'status' => $order->status
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
                $validated['items'] = array_filter($validated['items'], function($item) {
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
                            // If still pending, delete it
                            $existingItem->delete();
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

            // Recalculate order totals from all non-cancelled order items
            $order->refresh();
            $subtotalFromAllItems = $order->orderItems()
                ->where('status', '!=', 'cancelled')
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isNewOrder ? 'Order placed successfully' : 'Order updated successfully',
                'order' => $order->load(['orderItems.item', 'table']),
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
        // Group items by category - BEVERAGES go to BOT, everything else to KOT
        $kitchenItems = [];
        $barItems = [];

        foreach ($kotItems as $kotItem) {
            $item = $kotItem['item'];

            // Load category if not already loaded
            if (!$item->relationLoaded('category')) {
                $item->load('category');
            }

            // Check by category_id (3 is BEVERAGES) or category slug
            $isBeverage = false;

            if ($item->category_id == 3) {
                $isBeverage = true;
            } elseif ($item->category) {
                $categorySlug = strtolower($item->category->slug);
                $categoryName = strtoupper($item->category->name);
                $isBeverage = ($categorySlug === 'beverages' || $categoryName === 'BEVERAGES');
            }

            if ($isBeverage) {
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'order' => $order->load(['payment', 'orderItems.item', 'table', 'waiter']),
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
        $order = Order::with(['orderItems.item', 'orderItems.modifiers.modifier', 'table', 'waiter', 'payment'])
            ->findOrFail($orderId);

        return view('pos.receipt', compact('order'));
    }
}
