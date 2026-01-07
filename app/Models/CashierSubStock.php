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
     * Get or create sub stock for a main stock item.
     */
    public static function getOrCreateForItem(int $mainStockItemId): self
    {
        return self::firstOrCreate(
            ['main_stock_item_id' => $mainStockItemId],
            ['quantity' => 0]
        );
    }
}
