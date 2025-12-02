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
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'table_number' => $order->table ? $order->table->table_number : 'N/A',
                    'order_type' => $order->order_type,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at->format('h:i A'),
                    'items_count' => $order->orderItems->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $openOrders
        ]);
    }

    /**
     * Get order details for editing
     */
    public function getOrder($orderId)
    {
        $order = Order::with(['orderItems.item.modifiers', 'table'])
            ->findOrFail($orderId);

        $items = $order->orderItems->map(function ($orderItem) {
            return [
                'item_id' => $orderItem->item_id,
                'name' => $orderItem->item->name,
                'price' => $orderItem->unit_price,
                'quantity' => $orderItem->quantity,
                'modifiers' => []
            ];
        });

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
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.name' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $isNewOrder = empty($validated['order_id']);

            if ($isNewOrder) {
                // Create new order
                $order = Order::create([
                    'order_type' => $validated['order_type'],
                    'table_id' => $validated['table_id'] ?? null,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
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

                // Add all items
                $newItems = $validated['items'];
            } else {
                // Update existing order
                $order = Order::findOrFail($validated['order_id']);

                // Get existing item IDs
                $existingItemIds = $order->orderItems->pluck('item_id')->toArray();
                $newItemIds = array_column($validated['items'], 'item_id');

                // Find only newly added items
                $newItems = array_filter($validated['items'], function ($item) use ($existingItemIds) {
                    return !in_array($item['item_id'], $existingItemIds);
                });

                // Update placed_at timestamp
                $order->update(['placed_at' => now()]);
            }

            // Add items to order
            $subtotal = $order->subtotal ?? 0;
            $kotItems = []; // For KOT printing

            foreach ($newItems as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price'],
                    'subtotal' => $itemData['price'] * $itemData['quantity'],
                ]);

                $subtotal += $orderItem->subtotal;

                // Track for KOT
                $kotItems[] = [
                    'item' => $item,
                    'order_item' => $orderItem,
                    'quantity' => $itemData['quantity'],
                ];
            }

            // Update order totals
            $order->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
            ]);

            // Generate KOT/BOT for new items
            if (!empty($kotItems)) {
                $this->generateKOT($order, $kotItems);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isNewOrder ? 'Order placed successfully' : 'Order updated successfully',
                'order' => $order->load(['orderItems.item', 'table']),
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
        // Group items by print destination
        $kitchenItems = [];
        $barItems = [];

        foreach ($kotItems as $kotItem) {
            $item = $kotItem['item'];
            $printDestination = $item->print_destination ?? 'kitchen';

            if ($printDestination === 'bar' || $printDestination === 'both') {
                $barItems[] = $kotItem;
            }
            if ($printDestination === 'kitchen' || $printDestination === 'both') {
                $kitchenItems[] = $kotItem;
            }
        }

        // Create KOT for kitchen
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

            foreach ($kitchenItems as $kotItem) {
                KotItem::create([
                    'kot_id' => $kot->id,
                    'order_item_id' => $kotItem['order_item']->id,
                    'item_name' => $kotItem['item']->name,
                    'quantity' => $kotItem['quantity'],
                    'special_instructions' => $kotItem['order_item']->special_instructions,
                    'modifiers' => $kotItem['order_item']->modifiers ?? null,
                    'status' => 'pending',
                ]);
            }
        }

        // Create BOT for bar (using same KOT structure)
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

            foreach ($barItems as $botItem) {
                KotItem::create([
                    'kot_id' => $bot->id,
                    'order_item_id' => $botItem['order_item']->id,
                    'item_name' => $botItem['item']->name,
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
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:cash,card,credit',
            'amount_paid' => 'required|numeric|min:0',
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

            if ($validated['amount_paid'] < $totalAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount paid is less than total amount'
                ], 400);
            }

            // Create payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $validated['amount_paid'],
                'change_amount' => max(0, $validated['amount_paid'] - $totalAmount),
                'cashier_id' => Auth::id(),
                'payment_status' => 'completed',
            ]);

            // Update order
            $order->update([
                'status' => 'completed',
                'is_paid' => true,
                'completed_at' => now(),
            ]);

            // Free up table if dine-in
            if ($order->table_id) {
                $table = Table::find($order->table_id);
                $table->update([
                    'status' => 'available',
                    'current_order_id' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'order' => $order->load('payment'),
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
        $order = Order::with(['orderItems.item', 'table', 'waiter', 'payment'])
            ->findOrFail($orderId);

        return view('pos.receipt', compact('order'));
    }
}
