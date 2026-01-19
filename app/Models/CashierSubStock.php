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
        $mainStockItem = null;

        // Extract item and modifier from display name "Item (Modifier)" format
        $itemName = trim($displayName);
        $modifierName = null;

        if (preg_match('/^(.+?)\s*\(([^)]+)\)$/', $displayName, $matches)) {
            $itemName = trim($matches[1]);
            $modifierName = trim($matches[2]);
        }

        // Try multiple matching patterns
        $searchPatterns = [];

        if ($modifierName) {
            // Add all possible name format combinations
            $searchPatterns = [
                // Standard format: "Item Name - Modifier"
                $itemName . ' - ' . $modifierName,
                // Reversed: "Modifier - Item Name" (some items might be stored this way)
                $modifierName . ' - ' . $itemName,
                // With parentheses (same as display): "Item Name (Modifier)"
                $itemName . ' (' . $modifierName . ')',
                // Just modifier name alone (if it's unique)
                $modifierName,
                // Partial match patterns
            ];
        } else {
            $searchPatterns = [$itemName];
        }

        // Try exact matches first
        foreach ($searchPatterns as $pattern) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where('item_name', $pattern)
                ->first();

            if ($mainStockItem)
                break;
        }

        // Try case-insensitive exact match
        if (!$mainStockItem) {
            foreach ($searchPatterns as $pattern) {
                $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                    ->where('is_active', true)
                    ->whereRaw('LOWER(item_name) = ?', [strtolower($pattern)])
                    ->first();

                if ($mainStockItem)
                    break;
            }
        }

        // Try partial LIKE match with both item name and modifier
        if (!$mainStockItem && $modifierName) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where(function ($q) use ($itemName, $modifierName) {
                    $q->where('item_name', 'LIKE', '%' . $itemName . '%')
                        ->where('item_name', 'LIKE', '%' . $modifierName . '%');
                })
                ->first();
        }

        // Try matching just the modifier name (for items like "Sprite", "Coca Cola")
        if (!$mainStockItem && $modifierName) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->whereRaw('LOWER(item_name) = ?', [strtolower($modifierName)])
                ->first();
        }

        // Try exact item name only (no modifier)
        if (!$mainStockItem) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where('item_name', $itemName)
                ->first();
        }

        // If no matching MainStockItem found, log and return null
        if (!$mainStockItem) {
            \Log::warning('FG Stock Deduction: No matching MainStockItem found', [
                'display_name' => $displayName,
                'extracted_item_name' => $itemName,
                'extracted_modifier' => $modifierName,
                'item_id' => $itemId,
                'quantity_to_deduct' => $quantity,
            ]);
            return null;
        }

        // Get or create the CashierSubStock record
        $subStock = self::getOrCreateForItem($mainStockItem->id);

        // Deduct the quantity (allows negative stock)
        $subStock->deductStockForSale($quantity, $userId);

        \Log::info('FG Stock Deducted', [
            'main_stock_item' => $mainStockItem->item_name,
            'main_stock_item_id' => $mainStockItem->id,
            'quantity_deducted' => $quantity,
            'new_stock_quantity' => $subStock->quantity,
        ]);

        return $subStock;
    }

    /**
     * Deduct stock for sale using linked item ID and modifier ID.
     * This is the preferred method as it uses reliable ID matching instead of name matching.
     * If no MainStockItem exists, one will be auto-created.
     * 
     * @param int $itemId - The menu item ID
     * @param int|null $modifierId - The item modifier/portion ID (optional)
     * @param float $quantity - Quantity to deduct
     * @param int|null $userId - User who performed the action
     * @return self|null - The stock record or null if item not found in menu
     */
    public static function deductForSaleById(int $itemId, ?int $modifierId, float $quantity, ?int $userId = null): ?self
    {
        // Find MainStockItem by linked IDs (most reliable method)
        $mainStockItem = MainStockItem::where('item_type', 'finished_good')
            ->where('is_active', true)
            ->where('linked_item_id', $itemId)
            ->where('linked_item_modifier_id', $modifierId)
            ->first();

        // If no matching MainStockItem found, auto-create one
        if (!$mainStockItem) {
            // Get the menu item to create stock item
            $menuItem = \App\Models\Item::find($itemId);
            if (!$menuItem) {
                \Log::warning('FG Stock Deduction by ID: Menu item not found', [
                    'item_id' => $itemId,
                    'modifier_id' => $modifierId,
                    'quantity_to_deduct' => $quantity,
                ]);
                return null;
            }

            // Build the item name
            $itemName = $menuItem->name;
            if ($modifierId) {
                $modifier = \App\Models\ItemModifier::find($modifierId);
                if ($modifier) {
                    $itemName = $menuItem->name . ' - ' . $modifier->name;
                }
            }

            // Generate item code
            $itemCode = MainStockItem::generateItemCode('finished_good');

            // Create the MainStockItem with linked IDs
            $mainStockItem = MainStockItem::create([
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'unit_type' => 'quantity',
                'item_type' => 'finished_good',
                'linked_item_id' => $itemId,
                'linked_item_modifier_id' => $modifierId,
                'quantity' => 0,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            \Log::info('FG Stock: Auto-created MainStockItem', [
                'main_stock_item' => $mainStockItem->item_name,
                'main_stock_item_id' => $mainStockItem->id,
                'linked_item_id' => $itemId,
                'linked_modifier_id' => $modifierId,
            ]);
        }

        // Get or create the CashierSubStock record
        $subStock = self::getOrCreateForItem($mainStockItem->id);

        // Deduct the quantity (allows negative stock)
        $subStock->deductStockForSale($quantity, $userId);

        \Log::info('FG Stock Deducted by ID', [
            'main_stock_item' => $mainStockItem->item_name,
            'main_stock_item_id' => $mainStockItem->id,
            'linked_item_id' => $itemId,
            'linked_modifier_id' => $modifierId,
            'quantity_deducted' => $quantity,
            'new_stock_quantity' => $subStock->quantity,
        ]);

        return $subStock;
    }

    /**
     * Restore stock for a deleted/cancelled sale using linked item ID and modifier ID.
     * This is the reverse of deductForSaleById - adds stock back when order is deleted.
     * 
     * @param int $itemId - The menu item ID
     * @param int|null $modifierId - The item modifier/portion ID (optional)
     * @param float $quantity - Quantity to restore
     * @param int|null $userId - User who performed the action
     * @return self|null - The stock record or null if not found
     */
    public static function restoreForSaleById(int $itemId, ?int $modifierId, float $quantity, ?int $userId = null): ?self
    {
        // Find MainStockItem by linked IDs
        $mainStockItem = MainStockItem::where('item_type', 'finished_good')
            ->where('is_active', true)
            ->where('linked_item_id', $itemId)
            ->where('linked_item_modifier_id', $modifierId)
            ->first();

        // If no matching MainStockItem found, try without modifier
        if (!$mainStockItem && $modifierId === null) {
            $mainStockItem = MainStockItem::where('item_type', 'finished_good')
                ->where('is_active', true)
                ->where('linked_item_id', $itemId)
                ->whereNull('linked_item_modifier_id')
                ->first();
        }

        if (!$mainStockItem) {
            \Log::warning('FG Stock Restore by ID: No matching MainStockItem found', [
                'item_id' => $itemId,
                'modifier_id' => $modifierId,
                'quantity_to_restore' => $quantity,
            ]);
            return null;
        }

        // Get or create the CashierSubStock record
        $subStock = self::getOrCreateForItem($mainStockItem->id);

        // Add the quantity back
        $subStock->addStock($quantity, $userId);

        \Log::info('FG Stock Restored by ID', [
            'main_stock_item' => $mainStockItem->item_name,
            'main_stock_item_id' => $mainStockItem->id,
            'linked_item_id' => $itemId,
            'linked_modifier_id' => $modifierId,
            'quantity_restored' => $quantity,
            'new_stock_quantity' => $subStock->quantity,
        ]);

        return $subStock;
    }
}
