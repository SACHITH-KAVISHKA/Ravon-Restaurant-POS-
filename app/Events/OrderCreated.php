<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->load(['table', 'waiter', 'items.item']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
            new Channel('kitchen'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'order_type' => $this->order->order_type,
            'table' => $this->order->table ? [
                'id' => $this->order->table->id,
                'table_number' => $this->order->table->table_number,
            ] : null,
            'waiter' => [
                'id' => $this->order->waiter->id,
                'name' => $this->order->waiter->name,
            ],
            'items_count' => $this->order->items->count(),
            'total_amount' => $this->order->total_amount,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at->toISOString(),
        ];
    }
}
