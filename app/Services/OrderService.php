<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    public function __construct(
        protected KOTService $kotService
    ) {}

    /**
     * Create a new order.
     */
    public function createOrder(array $data): Order
    {
        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'order_type' => $data['order_type'],
                'table_id' => $data['table_id'] ?? null,
                'waiter_id' => $data['waiter_id'] ?? auth()->id(),
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'guest_count' => $data['guest_count'] ?? 1,
                'special_instructions' => $data['special_instructions'] ?? null,
                'created_by' => auth()->id(),
                'subtotal' => 0,
                'total_amount' => 0,
                'status' => 'pending',
            ]);

            // Add items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addItemToOrder($order, $itemData);
                }
            }

            // Calculate totals
            $this->calculateOrderTotal($order);

            // Update table if dine-in
            if ($order->table_id) {
                $table = Table::find($order->table_id);
                $table->update([
                    'status' => 'ordered',
                    'current_order_id' => $order->id,
                ]);
            }

            // Generate KOTs
            $this->kotService->generateKOTsForOrder($order);

            DB::commit();

            // Broadcast
            broadcast(new \App\Events\OrderCreated($order));

            return $order->fresh(['orderItems.item', 'table']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add item to order.
     */
    public function addItemToOrder(Order $order, array $itemData): OrderItem
    {
        $orderItem = $order->orderItems()->create([
            'item_id' => $itemData['item_id'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'special_instructions' => $itemData['special_instructions'] ?? null,
            'subtotal' => $itemData['unit_price'] * $itemData['quantity'],
            'status' => 'pending',
        ]);

        // Add modifiers
        if (isset($itemData['modifiers']) && is_array($itemData['modifiers'])) {
            foreach ($itemData['modifiers'] as $modifierData) {
                $orderItem->modifiers()->create([
                    'modifier_id' => $modifierData['id'],
                    'price_adjustment' => $modifierData['price_adjustment'] ?? 0,
                ]);
            }
        }

        // Recalculate subtotal with modifiers
        $modifiersTotal = $orderItem->modifiers->sum('price_adjustment');
        $orderItem->update([
            'subtotal' => ($orderItem->unit_price + $modifiersTotal) * $orderItem->quantity
        ]);

        return $orderItem;
    }

    /**
     * Update order item quantity.
     */
    public function updateItemQuantity(OrderItem $orderItem, int $quantity): OrderItem
    {
        $modifiersTotal = $orderItem->modifiers->sum('price_adjustment');
        
        $orderItem->update([
            'quantity' => $quantity,
            'subtotal' => ($orderItem->unit_price + $modifiersTotal) * $quantity,
        ]);

        $this->calculateOrderTotal($orderItem->order);

        return $orderItem->fresh();
    }

    /**
     * Remove item from order.
     */
    public function removeItemFromOrder(OrderItem $orderItem): bool
    {
        $order = $orderItem->order;
        $orderItem->delete();
        
        $this->calculateOrderTotal($order);

        broadcast(new \App\Events\OrderItemRemoved($order, $orderItem->id));

        return true;
    }

    /**
     * Calculate order total.
     */
    public function calculateOrderTotal(Order $order): Order
    {
        $subtotal = $order->orderItems->sum('subtotal');
        
        // Apply service charge (10%)
        $serviceCharge = $subtotal * 0.10;
        
        // Apply tax (5%)
        $taxAmount = ($subtotal + $serviceCharge) * 0.05;
        
        $total = $subtotal + $serviceCharge + $taxAmount - $order->discount_amount + $order->delivery_fee;

        $order->update([
            'subtotal' => $subtotal,
            'service_charge' => $serviceCharge,
            'tax_amount' => $taxAmount,
            'total_amount' => max(0, $total),
        ]);

        return $order->fresh();
    }

    /**
     * Apply discount to order.
     */
    public function applyDiscount(Order $order, float $amount, string $type, ?string $reason = null): Order
    {
        $order->update([
            'discount_amount' => $amount,
            'discount_type' => $type,
            'discount_reason' => $reason,
        ]);

        $this->calculateOrderTotal($order);

        return $order->fresh();
    }

    /**
     * Update order status.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        if ($status === 'completed') {
            $order->update(['completed_at' => now()]);
            
            // Update table
            if ($order->table) {
                $order->table->update([
                    'status' => 'available',
                    'current_order_id' => null,
                ]);
            }
        }

        broadcast(new \App\Events\OrderStatusUpdated($order));

        return $order->fresh();
    }

    /**
     * Cancel order.
     */
    public function cancelOrder(Order $order, string $reason): Order
    {
        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $reason,
            ]);

            // Update table
            if ($order->table) {
                $order->table->update([
                    'status' => 'available',
                    'current_order_id' => null,
                ]);
            }

            // Cancel KOTs
            $order->kots()->update(['status' => 'cancelled']);

            DB::commit();

            broadcast(new \App\Events\OrderCancelled($order));

            return $order->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create split order (for table splitting).
     */
    public function createSplitOrder(Order $originalOrder, array $itemIds): Order
    {
        $newOrder = Order::create([
            'order_type' => $originalOrder->order_type,
            'waiter_id' => $originalOrder->waiter_id,
            'customer_name' => $originalOrder->customer_name,
            'guest_count' => 1,
            'special_instructions' => 'Split from Order #' . $originalOrder->order_number,
            'created_by' => auth()->id(),
            'subtotal' => 0,
            'total_amount' => 0,
            'status' => 'pending',
        ]);

        // Move items to new order
        foreach ($itemIds as $itemId) {
            $orderItem = OrderItem::find($itemId);
            if ($orderItem && $orderItem->order_id === $originalOrder->id) {
                $orderItem->update(['order_id' => $newOrder->id]);
            }
        }

        // Recalculate both orders
        $this->calculateOrderTotal($newOrder);
        $this->calculateOrderTotal($originalOrder);

        return $newOrder;
    }

    /**
     * Get active orders.
     */
    public function getActiveOrders()
    {
        return Order::active()
            ->with(['table', 'waiter', 'orderItems.item'])
            ->latest()
            ->get();
    }

    /**
     * Get order details.
     */
    public function getOrderDetails(int $orderId)
    {
        return Order::with([
            'table.floor',
            'waiter',
            'orderItems.item.category',
            'orderItems.modifiers.modifier',
            'kots.kitchenStation',
            'payment',
            'deliveryOrder'
        ])->findOrFail($orderId);
    }
}
