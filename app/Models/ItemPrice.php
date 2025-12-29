<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'item_modifier_id',
        'price_type',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the item modifier (portion/size).
     */
    public function itemModifier(): BelongsTo
    {
        return $this->belongsTo(ItemModifier::class);
    }

    /**
     * Scope to get prices by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('price_type', $type);
    }

    /**
     * Scope to get item prices (not modifiers).
     */
    public function scopeForItem($query)
    {
        return $query->whereNull('item_modifier_id');
    }

    /**
     * Scope to get modifier prices.
     */
    public function scopeForModifier($query)
    {
        return $query->whereNotNull('item_modifier_id');
    }

    /**
     * Available price types (excluding 'default' as it's stored in items/item_modifiers tables)
     */
    public static function availablePriceTypes()
    {
        return [
            'pickme' => 'Pick Me',
            'ubereats' => 'Uber Eats',
            'deliveroo' => 'Deliveroo',
            // Add more as needed
        ];
    }
}
