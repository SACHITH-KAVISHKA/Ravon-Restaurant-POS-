<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_address',
        'delivery_city',
        'delivery_postcode',
        'delivery_instructions',
        'driver_name',
        'driver_phone',
        'estimated_delivery_time',
        'actual_delivery_time',
        'delivery_status',
    ];

    protected $casts = [
        'estimated_delivery_time' => 'datetime',
        'actual_delivery_time' => 'datetime',
    ];

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope for pending deliveries.
     */
    public function scopePending($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    /**
     * Scope for assigned deliveries.
     */
    public function scopeAssigned($query)
    {
        return $query->where('delivery_status', 'assigned');
    }

    /**
     * Scope for in-transit deliveries.
     */
    public function scopeInTransit($query)
    {
        return $query->where('delivery_status', 'picked_up');
    }
}
