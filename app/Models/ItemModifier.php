<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'name',
        'type',
        'price_adjustment',
        'is_active',
        'pork_available',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
        'pork_available' => 'boolean',
    ];

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get order item modifiers.
     */
    public function orderItemModifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class, 'modifier_id');
    }

    /**
     * Get the item prices for this modifier.
     */
    public function itemPrices(): HasMany
    {
        return $this->hasMany(ItemPrice::class);
    }

    /**
     * Get price by type for this modifier (pickme, ubereats, etc.).
     * Returns the special price if exists, otherwise returns the default price_adjustment from item_modifiers table.
     */
    public function getPriceByType($type)
    {
        if (!$type || $type === 'default') {
            return $this->price_adjustment;
        }

        $itemPrice = $this->itemPrices()
            ->where('price_type', $type)
            ->first();

        return $itemPrice ? $itemPrice->price : $this->price_adjustment;
    }

    /**
     * Scope to get only active modifiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the recipes for this modifier/portion.
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(ItemRecipe::class);
    }
}
