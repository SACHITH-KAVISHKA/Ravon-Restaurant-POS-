<?php

namespace App\Events;

use App\Models\Kot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KOTGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $kot;

    /**
     * Create a new event instance.
     */
    public function __construct(Kot $kot)
    {
        $this->kot = $kot->load(['order.table', 'kitchenStation', 'items']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('kitchen'),
            new Channel('station.' . $this->kot->kitchen_station_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'kot.generated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->kot->id,
            'kot_number' => $this->kot->kot_number,
            'order_id' => $this->kot->order_id,
            'order_number' => $this->kot->order->order_number,
            'table' => $this->kot->order->table ? [
                'id' => $this->kot->order->table->id,
                'table_number' => $this->kot->order->table->table_number,
            ] : null,
            'kitchen_station' => [
                'id' => $this->kot->kitchenStation->id,
                'name' => $this->kot->kitchenStation->name,
            ],
            'items' => $this->kot->items->map(function ($item) {
                return [
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'modifiers' => $item->modifiers,
                    'special_instructions' => $item->special_instructions,
                ];
            }),
            'status' => $this->kot->status,
            'created_at' => $this->kot->created_at->toISOString(),
        ];
    }
}
