<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_request_id',
        'item_id',
        'item_modifier_id',
        'item_name',
        'requested_quantity',
        'approved_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
    ];

    /**
     * Get the parent stock request.
     */
    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
    }

    /**
     * Get the menu item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withoutGlobalScope('active');
    }

    /**
     * Get the item modifier (portion/size).
     */
    public function itemModifier(): BelongsTo
    {
        return $this->belongsTo(ItemModifier::class)->withoutGlobalScope('active');
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'partially_approved' => 'blue',
            default => 'gray',
        };
    }
}
