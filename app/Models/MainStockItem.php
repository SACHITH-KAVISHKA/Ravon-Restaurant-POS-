<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainStockItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_name',
        'unit_type',
        'item_type',
        'linked_item_id',
        'linked_item_modifier_id',
        'quantity',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    /**
     * Attributes to append to the model's JSON form.
     */
    protected $appends = [
        'unit_abbreviation',
    ];

    /**
     * Unit type labels for display
     */
    public const UNIT_TYPES = [
        'kilogram' => 'Kilogram (kg)',
        'gram' => 'Gram (g)',
        'liter' => 'Liter (L)',
        'milliliter' => 'Milliliter (ml)',
        'quantity' => 'Quantity (pcs)',
    ];

    /**
     * Unit type abbreviations
     */
    public const UNIT_ABBREVIATIONS = [
        'kilogram' => 'kg',
        'gram' => 'g',
        'liter' => 'L',
        'milliliter' => 'ml',
        'quantity' => 'pcs',
    ];

    /**
     * Item type labels for display
     */
    public const ITEM_TYPES = [
        'raw_material' => 'Raw Material',
        'finished_good' => 'Finished Good',
        'other' => 'Other',
    ];

    /**
     * Get the user who created this item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this item.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the linked menu item (for finished goods).
     */
    public function linkedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'linked_item_id');
    }

    /**
     * Get the linked modifier/portion (for finished goods with portions).
     */
    public function linkedModifier(): BelongsTo
    {
        return $this->belongsTo(ItemModifier::class, 'linked_item_modifier_id');
    }

    /**
     * Get the transactions for this item.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(MainStockTransaction::class);
    }

    /**
     * Get unit abbreviation.
     */
    public function getUnitAbbreviationAttribute(): string
    {
        return self::UNIT_ABBREVIATIONS[$this->unit_type] ?? $this->unit_type;
    }

    /**
     * Get unit type label.
     */
    public function getUnitTypeLabelAttribute(): string
    {
        return self::UNIT_TYPES[$this->unit_type] ?? $this->unit_type;
    }

    /**
     * Get item type label.
     */
    public function getItemTypeLabelAttribute(): string
    {
        return self::ITEM_TYPES[$this->item_type] ?? $this->item_type;
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Check if stock is out.
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Add stock quantity and create transaction.
     */
    public function addStock(float $qty, int $userId, string $type = 'stock_in', ?string $reference = null, ?string $notes = null): MainStockTransaction
    {
        $quantityBefore = $this->quantity;
        $this->quantity += $qty;
        $this->updated_by = $userId;
        $this->save();

        return $this->createTransaction($type, $qty, $quantityBefore, $this->quantity, $userId, $reference, $notes);
    }

    /**
     * Deduct stock quantity and create transaction.
     */
    public function deductStock(float $qty, int $userId, string $type = 'stock_out', ?string $reference = null, ?string $notes = null): ?MainStockTransaction
    {
        if ($this->quantity < $qty) {
            return null; // Not enough stock
        }

        $quantityBefore = $this->quantity;
        $this->quantity -= $qty;
        $this->updated_by = $userId;
        $this->save();

        return $this->createTransaction($type, $qty, $quantityBefore, $this->quantity, $userId, $reference, $notes);
    }

    /**
     * Adjust stock to a specific quantity.
     */
    public function adjustStock(float $newQty, int $userId, ?string $notes = null): MainStockTransaction
    {
        $quantityBefore = $this->quantity;
        $difference = $newQty - $this->quantity;

        $this->quantity = $newQty;
        $this->updated_by = $userId;
        $this->save();

        return $this->createTransaction('adjustment', abs($difference), $quantityBefore, $this->quantity, $userId, null, $notes);
    }

    /**
     * Create a stock transaction record.
     */
    protected function createTransaction(string $type, float $qty, float $before, float $after, int $userId, ?string $reference = null, ?string $notes = null): MainStockTransaction
    {
        return MainStockTransaction::create([
            'main_stock_item_id' => $this->id,
            'transaction_type' => $type,
            'quantity' => $qty,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_number' => $reference,
            'notes' => $notes,
            'performed_by' => $userId,
        ]);
    }

    /**
     * Generate a unique item code.
     * @param string $type The item type (raw_material, finished_good, other)
     * @param int $offset Additional offset to add (for bulk creation when codes aren't saved yet)
     */
    public static function generateItemCode(string $type = 'other', int $offset = 0): string
    {
        $prefix = match ($type) {
            'raw_material' => 'RM',
            'finished_good' => 'FG',
            default => 'OT',
        };

        $lastItem = self::where('item_code', 'like', $prefix . '%')
            ->orderBy('item_code', 'desc')
            ->first();

        if ($lastItem) {
            $lastNumber = (int) substr($lastItem->item_code, 2);
            $newNumber = $lastNumber + 1 + $offset;
        } else {
            $newNumber = 1 + $offset;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for items by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope for low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }
}
