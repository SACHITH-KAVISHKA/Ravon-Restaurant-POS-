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
     * Deduct stock quantity for sales - ALLOWS NEGATIVE STOCK.
     * This method will deduct stock even if it results in negative quantity.
     * Used when items are sold through POS.
     */
    public function deductStockForSale(float $quantity, ?int $userId = null): void
    {
        $this->quantity -= $quantity;
        if ($userId) {
            $this->last_updated_by = $userId;
        }
        $this->save();
    }

    /**
     * Deduct stock for sale - creates stock entry if it doesn't exist.
     * This is used when selling items through POS for beverages and desserts.
     * @param int $itemId - The item ID
     * @param int|null $modifierId - The modifier ID (for portion/size)
     * @param float $quantity - Quantity to deduct
     * @param int|null $userId - User who performed the action
     * @return self - The stock record (newly created or existing)
     */
    public static function deductForSale(int $itemId, ?int $modifierId, float $quantity, ?int $userId = null): self
    {
        // Get or create the stock record
        $stock = self::getOrCreateForItem($itemId, $modifierId);

        // Deduct the quantity (allows negative stock)
        $stock->deductStockForSale($quantity, $userId);

        return $stock;
    }

    /**
     * Deduct stock for sale using item ID and display name.
     * Extracts the modifier name from display name (format: "Item Name (Modifier)") 
     * and finds the modifier ID.
     * 
     * @param int $itemId - The item ID
     * @param string $displayName - The display name (e.g., "Coca Cola (250ml)")
     * @param float $quantity - Quantity to deduct
     * @param int|null $userId - User who performed the action
     * @return self|null - The stock record or null if unable to process
     */
    public static function deductForSaleByDisplayName(int $itemId, string $displayName, float $quantity, ?int $userId = null): ?self
    {
        $modifierId = null;

        // Check if display name contains a modifier in parentheses
        // Format: "Item Name (Modifier Name)" e.g., "Coca Cola (250ml)"
        if (preg_match('/\(([^)]+)\)$/', $displayName, $matches)) {
            $modifierName = trim($matches[1]);

            // Find the modifier for this item with this name
            $modifier = ItemModifier::where('item_id', $itemId)
                ->where('name', $modifierName)
                ->first();

            if ($modifier) {
                $modifierId = $modifier->id;
            }
        }

        // Deduct the stock
        return self::deductForSale($itemId, $modifierId, $quantity, $userId);
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
        $mainStockQuery = MainStock::where('item_id', $itemId);
        if ($modifierId) {
            $mainStockQuery->where('item_modifier_id', $modifierId);
        } else {
            $mainStockQuery->whereNull('item_modifier_id');
        }
        $mainStock = $mainStockQuery->first();

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
