<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'item_modifier_id',
        'main_stock_item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withoutGlobalScope('active');
    }

    /**
     * Get the portion/modifier this recipe belongs to (if any).
     */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(ItemModifier::class, 'item_modifier_id')->withoutGlobalScope('active');
    }

    /**
     * Get the raw material from main stock.
     */
    public function mainStockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class);
    }

    /**
     * Get the unit abbreviation from the related main stock item.
     */
    public function getUnitAbbreviationAttribute(): string
    {
        return $this->mainStockItem ? $this->mainStockItem->unit_abbreviation : '';
    }
}
