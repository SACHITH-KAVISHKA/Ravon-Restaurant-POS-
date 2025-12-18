<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'item_modifier_id',
        'quantity',
        'unit',
        'notes',
        'last_updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
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
     * Get the user who last updated this stock.
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Get the display name (Item Name + Portion if exists).
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->item->name ?? 'Unknown Item';
        if ($this->itemModifier) {
            $name .= ' (' . $this->itemModifier->name . ')';
        }
        return $name;
    }

    /**
     * Add stock quantity (when receiving from main stock).
     */
    public function addStock(float $quantity, ?int $userId = null): void
    {
        $this->quantity += $quantity;
        if ($userId) {
            $this->last_updated_by = $userId;
        }
        $this->save();
    }

    /**
     * Deduct stock quantity (when selling items).
     */
    public function deductStock(float $quantity, ?int $userId = null): bool
    {
        if ($this->quantity < $quantity) {
            return false; // Not enough stock
        }

        $this->quantity -= $quantity;
        if ($userId) {
            $this->last_updated_by = $userId;
        }
        $this->save();
        return true;
    }

    /**
     * Get or create stock record for an item + modifier combination.
     */
    public static function getOrCreateForItem(int $itemId, ?int $modifierId = null, string $unit = 'pcs'): self
    {
        return self::firstOrCreate(
            ['item_id' => $itemId, 'item_modifier_id' => $modifierId],
            ['quantity' => 0, 'unit' => $unit]
        );
    }

    /**
     * Transfer stock from main stock to restaurant stock.
     */
    public static function receiveFromMainStock(int $itemId, ?int $modifierId, float $quantity, int $userId): bool
    {
        // Get or create restaurant stock record
        $restaurantStock = self::getOrCreateForItem($itemId, $modifierId);

        // Get main stock
        $mainStock = MainStock::where('item_id', $itemId)
            ->where('item_modifier_id', $modifierId)
            ->first();

        if (!$mainStock || $mainStock->quantity < $quantity) {
            return false; // Main stock doesn't have enough
        }

        // Deduct from main stock
        $mainStock->deductStock($quantity, $userId);

        // Add to restaurant stock
        $restaurantStock->addStock($quantity, $userId);

        return true;
    }
}
