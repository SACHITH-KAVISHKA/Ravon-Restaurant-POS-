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
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
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
}
