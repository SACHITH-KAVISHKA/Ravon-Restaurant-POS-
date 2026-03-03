<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wastage extends Model
{
    use HasFactory;

    protected $fillable = [
        'wastage_id',
        'wastage_date',
        'main_stock_item_id',
        'item_name',
        'item_code',
        'item_type',
        'quantity_before',
        'quantity_wasted',
        'quantity_after',
        'unit',
        'price',
        'wastage_amount',
        'reason',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:3',
        'quantity_wasted' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'price' => 'decimal:2',
        'wastage_amount' => 'decimal:2',
        'wastage_date' => 'datetime',
    ];

    public const REASONS = [
        'expired' => 'Expired',
        'damaged' => 'Damaged',
        'spoiled' => 'Spoiled',
        'preparation_waste' => 'Preparation Waste',
        'overcooked' => 'Overcooked / Burnt',
        'dropped' => 'Dropped / Spilled',
        'quality_issue' => 'Quality Issue',
        'other' => 'Other',
    ];

    /**
     * Relationships
     */
    public function mainStockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Accessors
     */
    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /**
     * Generate a unique wastage ID like WST20260225-0001
     */
    public static function generateWastageId(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'WST' . $date;
        $lastWastage = self::where('wastage_id', 'like', $prefix . '%')
            ->orderBy('wastage_id', 'desc')
            ->first();

        if ($lastWastage) {
            $lastNumber = (int) substr($lastWastage->wastage_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scopes
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('wastage_date', [$start, $end]);
    }

    public function scopeForItem($query, $mainStockItemId)
    {
        return $query->where('main_stock_item_id', $mainStockItemId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('wastage_date', today());
    }

    public function scopeOfType($query, string $itemType)
    {
        return $query->where('item_type', $itemType);
    }

    public function scopeByPerformer($query, int $userId)
    {
        return $query->where('performed_by', $userId);
    }
}
