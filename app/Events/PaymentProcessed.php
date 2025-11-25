<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment->load(['order.table', 'cashier']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('payments'),
            new Channel('orders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'payment.processed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'order_id' => $this->payment->order_id,
            'order_number' => $this->payment->order->order_number,
            'table' => $this->payment->order->table ? [
                'id' => $this->payment->order->table->id,
                'table_number' => $this->payment->order->table->table_number,
            ] : null,
            'payment_method' => $this->payment->payment_method,
            'total_amount' => $this->payment->total_amount,
            'amount_received' => $this->payment->amount_received,
            'change_amount' => $this->payment->change_amount,
            'cashier' => [
                'id' => $this->payment->cashier->id,
                'name' => $this->payment->cashier->name,
            ],
            'created_at' => $this->payment->created_at->toISOString(),
        ];
    }
}
