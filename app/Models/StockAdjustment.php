<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_id',
        'adjustment_date',
        'cashier_name',
        'notes',
        'main_stock_item_id',
        'item_name',
        'system_qty',
        'actual_qty',
        'variance_qty',
        'price',
        'variance_amount',
        'adjustment_type',
        'stock_location',
        'performed_by',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'actual_qty' => 'decimal:3',
        'variance_qty' => 'decimal:3',
        'price' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'adjustment_date' => 'datetime',
    ];

    /**
     * Get the main stock item.
     */
    public function mainStockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class);
    }

    /**
     * Get the user who performed the adjustment.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Generate unique adjustment ID.
     */
    public static function generateAdjustmentId(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'ADJ' . $date;

        $lastAdjustment = self::where('adjustment_id', 'like', $prefix . '%')
            ->orderBy('adjustment_id', 'desc')
            ->first();

        if ($lastAdjustment) {
            $lastNumber = (int) substr($lastAdjustment->adjustment_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for adjustments within a date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('adjustment_date', [$start, $end]);
    }

    /**
     * Scope for adjustments by item.
     */
    public function scopeForItem($query, $mainStockItemId)
    {
        return $query->where('main_stock_item_id', $mainStockItemId);
    }

    /**
     * Scope for today's adjustments.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('adjustment_date', today());
    }
}
