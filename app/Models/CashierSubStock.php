<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierSubStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_stock_item_id',
        'quantity',
        'last_updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    /**
     * Get the main stock item.
     */
    public function mainStockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class);
    }

    /**
     * Get the user who last updated this stock.
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Get display name from main stock item.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->mainStockItem->item_name ?? 'Unknown Item';
    }

    /**
     * Get unit abbreviation from main stock item.
     */
    public function getUnitAbbreviationAttribute(): string
    {
        return $this->mainStockItem->unit_abbreviation ?? 'pcs';
    }

    /**
     * Add stock quantity.
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
     * Deduct stock quantity.
     */
    public function deductStock(float $quantity, ?int $userId = null): bool
    {
        if ($this->quantity < $quantity) {
            return false;
        }

        $this->quantity -= $quantity;
        if ($userId) {
            $this->last_updated_by = $userId;
        }
        $this->save();
        return true;
    }

    /**
     * Deduct stock for sale - ALLOWS NEGATIVE STOCK.
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
     * Get or create sub stock for a main stock item.
     */
    public static function getOrCreateForItem(int $mainStockItemId): self
    {
        return self::firstOrCreate(
            ['main_stock_item_id' => $mainStockItemId],
            ['quantity' => 0]
        );
    }

    /**
     * Deduct stock for sale using item ID and display name.
     * Finds the MainStockItem by matching the item name (which includes portion).
     * The display name format is: "Item Name" or "Item Name (Modifier)" 
     * The MainStockItem name format is: "Item Name" or "Item Name - Modifier"
     * 
     * @param int $itemId - The menu item ID (not used for matching, just for reference)
     * @param string $displayName - The display name (e.g., "Watalappan (small)")
     * @param float $quantity - Quantity to deduct
     * @param int|null $userId - User who performed the action
     * @return self|null - The stock record or null if not found
     */
    public static function deductForSaleByDisplayName(int $itemId, string $displayName, float $quantity, ?int $userId = null): ?self
    {
        // Try different name patterns to find the MainStockItem
        // Pattern 1: Exact match with display name
        // Pattern 2: "Item Name - Modifier" format (e.g., "Watalappan - small")
        // Pattern 3: Just the item name (without modifier)

        $mainStockItem = null;

        // Extract item and modifier from display name "Item (Modifier)" format
        $itemName = $displayName;
        $modifierName = null;

        if (preg_match('/^(.+?)\s*\(([^)]+)\)$/', $displayName, $matches)) {
            $itemName = trim($matches[1]);
            $modifierName = trim($matches[2]);
        }

        // Try to find matching MainStockItem (finished_good type only)
        // Pattern 1: Exact match "Item Name - Modifier"
        if ($modifierName) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where('item_name', $itemName . ' - ' . $modifierName)
                ->first();
        }

        // Pattern 2: Try with lowercase modifier
        if (!$mainStockItem && $modifierName) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where('item_name', 'LIKE', $itemName . ' - ' . $modifierName)
                ->first();
        }

        // Pattern 3: Try partial match with item name containing both
        if (!$mainStockItem && $modifierName) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where('item_name', 'LIKE', '%' . $itemName . '%')
                ->where('item_name', 'LIKE', '%' . $modifierName . '%')
                ->first();
        }

        // Pattern 4: Try exact item name only (no modifier)
        if (!$mainStockItem) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where('item_name', $itemName)
                ->first();
        }

        // If no matching MainStockItem found, return null
        if (!$mainStockItem) {
            return null;
        }

        // Get or create the CashierSubStock record
        $subStock = self::getOrCreateForItem($mainStockItem->id);

        // Deduct the quantity (allows negative stock)
        $subStock->deductStockForSale($quantity, $userId);

        return $subStock;
    }
}
