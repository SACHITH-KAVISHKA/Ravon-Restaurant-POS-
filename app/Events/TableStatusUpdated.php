<?php

namespace App\Events;

use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $table;

    /**
     * Create a new event instance.
     */
    public function __construct(Table $table)
    {
        $this->table = $table->load(['currentOrder']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('tables'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'table.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->table->id,
            'table_number' => $this->table->table_number,
            'status' => $this->table->status,
            'capacity' => $this->table->capacity,
            'current_order_id' => $this->table->current_order_id,
            'updated_at' => $this->table->updated_at->toISOString(),
        ];
    }
}
