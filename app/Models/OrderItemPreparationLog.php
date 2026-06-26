<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemPreparationLog extends Model
{
    use HasFactory;

    protected $table = 'order_item_preparation_logs';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'item_id',
        'item_modifier_id',
        'item_display_name',
        'quantity',
        'item_status',
        'prepared_at',
        'delivered_at',
        'preparation_minutes',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'item_id' => 'integer',
        'item_modifier_id' => 'integer',
        'quantity' => 'integer',
        'preparation_minutes' => 'integer',
        'prepared_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Scope: only records that should appear in reports (exclude cancelled/deleted).
     */
    public function scopeReportable($query)
    {
        return $query->whereNotIn('item_status', ['cancelled', 'deleted']);
    }

    /**
     * Scope: only records that have not yet been delivered.
     */
    public function scopeUndelivered($query)
    {
        return $query->whereNull('delivered_at');
    }

    /**
     * Scope: only records that have been delivered.
     */
    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the menu item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the item modifier/portion.
     */
    public function itemModifier(): BelongsTo
    {
        return $this->belongsTo(ItemModifier::class, 'item_modifier_id');
    }
}
