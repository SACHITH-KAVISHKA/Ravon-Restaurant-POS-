<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\KOTService;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private KOTService $kotService
    ) {}

    /**
     * Create new order
     */
    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'nullable|exists:tables,id',
            'order_type' => 'required|in:dine_in,takeaway,delivery,third_party',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string',
            'items.*.modifiers' => 'nullable|array',
            'items.*.modifiers.*.modifier_id' => 'required|exists:item_modifiers,id',
        ]);

        $order = $this->orderService->createOrder([
            'table_id' => $request->table_id,
            'order_type' => $request->order_type,
            'waiter_id' => $request->user()->id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
        ], $request->items);

        // Generate KOTs
        $this->kotService->generateKOTsForOrder($order);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order->load(['activeItems.item', 'kots']),
        ], 201);
    }

    /**
     * Get order details
     */
    public function show(Order $order)
    {
        $order->load([
            'table.floor',
            'waiter',
            'activeItems.item.category',
            'activeItems.modifiers.modifier',
            'kots.kitchenStation',
            'payment',
        ]);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update order
     */
    public function update(Request $request, Order $order)
    {
        $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'special_instructions' => 'nullable|string',
        ]);

        $order->update($request->only([
            'customer_name',
            'customer_phone',
            'special_instructions',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order,
        ]);
    }

    /**
     * Add item to existing order
     */
    public function addItem(Request $request, Order $order)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'special_instructions' => 'nullable|string',
            'modifiers' => 'nullable|array',
            'modifiers.*.modifier_id' => 'required|exists:item_modifiers,id',
        ]);

        if ($order->status === 'completed' || $order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add items to completed or cancelled orders',
            ], 422);
        }

        $orderItem = $this->orderService->addItemToOrder($order, [
            'item_id' => $request->item_id,
            'quantity' => $request->quantity,
            'special_instructions' => $request->special_instructions,
            'modifiers' => $request->modifiers ?? [],
        ]);

        // Generate KOT for new item
        $this->kotService->generateKOTsForOrder($order->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Item added successfully',
            'data' => $orderItem->load(['item', 'modifiers']),
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,ready,served,completed,cancelled',
        ]);

        $order = $this->orderService->updateStatus($order, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'data' => $order,
        ]);
    }

    /**
     * Apply discount to order
     */
    public function applyDiscount(Request $request, Order $order)
    {
        $request->validate([
            'discount_type' => 'required|in:percentage,fixed',
            'discount_amount' => 'required|numeric|min:0',
            'discount_reason' => 'nullable|string',
        ]);

        $order = $this->orderService->applyDiscount(
            $order,
            $request->discount_type,
            $request->discount_amount,
            $request->discount_reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Discount applied',
            'data' => $order,
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, Order $order)
    {
        $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        $order = $this->orderService->cancelOrder(
            $order,
            $request->cancellation_reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled',
            'data' => $order,
        ]);
    }

    /**
     * List orders with filters
     */
    public function index(Request $request)
    {
        $query = Order::with(['table', 'waiter', 'items']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        $orders = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}
