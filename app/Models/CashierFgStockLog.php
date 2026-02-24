<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierFgStockLog extends Model
{
    use HasFactory;

    protected $table = 'cashier_fg_stock_logs';

    protected $fillable = [
        'main_stock_item_id',
        'item_name',
        'item_code',
        'action_type',
        'quantity_before',
        'quantity_after',
        'quantity_changed',
        'reference_type',
        'reference_id',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'quantity_changed' => 'decimal:3',
    ];

    /**
     * Action type labels for display.
     */
    public const ACTION_LABELS = [
        'sale_deduct' => 'Sale Deduction',
        'sale_restore' => 'Sale Restore (Order Deleted)',
        'transfer_in' => 'Transfer In',
        'adjustment' => 'Stock Adjustment',
        'void_restore' => 'Void Restore',
    ];

    /**
     * Action type badge colors for UI.
     */
    public const ACTION_COLORS = [
        'sale_deduct' => 'red',
        'sale_restore' => 'blue',
        'transfer_in' => 'green',
        'adjustment' => 'yellow',
        'void_restore' => 'purple',
    ];

    /**
     * Get the main stock item.
     */
    public function mainStockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the action label for display.
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action_type] ?? $this->action_type;
    }

    /**
     * Get the action badge color.
     */
    public function getActionColorAttribute(): string
    {
        return self::ACTION_COLORS[$this->action_type] ?? 'gray';
    }

    /**
     * Create a log entry for a Finished Goods stock change.
     *
     * @param int $mainStockItemId
     * @param string $actionType
     * @param float $quantityBefore
     * @param float $quantityAfter
     * @param string|null $referenceType
     * @param string|null $referenceId
     * @param string|null $notes
     * @param int|null $userId
     * @return self
     */
    public static function log(
        int $mainStockItemId,
        string $actionType,
        float $quantityBefore,
        float $quantityAfter,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
        ?int $userId = null
    ): self {
        $mainStockItem = MainStockItem::find($mainStockItemId);

        return self::create([
            'main_stock_item_id' => $mainStockItemId,
            'item_name' => $mainStockItem->item_name ?? 'Unknown',
            'item_code' => $mainStockItem->item_code ?? null,
            'action_type' => $actionType,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'quantity_changed' => $quantityAfter - $quantityBefore,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'performed_by' => $userId,
        ]);
    }

    /**
     * Scope for logs by item.
     */
    public function scopeForItem($query, int $mainStockItemId)
    {
        return $query->where('main_stock_item_id', $mainStockItemId);
    }

    /**
     * Scope for logs within a date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope for today's logs.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for a specific action type.
     */
    public function scopeOfAction($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }
}
