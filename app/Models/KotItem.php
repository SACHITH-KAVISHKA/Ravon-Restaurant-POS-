<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KotItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'kot_id',
        'order_item_id',
        'item_name',
        'quantity',
        'special_instructions',
        'modifiers',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'modifiers' => 'array',
    ];

    /**
     * Get the KOT.
     */
    public function kot(): BelongsTo
    {
        return $this->belongsTo(Kot::class);
    }

    /**
     * Get the order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Scope for pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for preparing items.
     */
    public function scopePreparing($query)
    {
        return $query->where('status', 'preparing');
    }
}
