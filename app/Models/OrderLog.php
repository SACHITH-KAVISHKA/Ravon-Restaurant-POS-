<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class OrderLog extends Model
{
    use HasFactory;

    /**
     * Disable updated_at timestamp since logs are immutable
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'order_number',
        'action',
        'old_status',
        'new_status',
        'reason',
        'old_values',
        'new_values',
        'description',
        'performed_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Action types constants
     */
    const ACTION_CREATED = 'created';
    const ACTION_STATUS_CHANGED = 'status_changed';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_ITEM_ADDED = 'item_added';
    const ACTION_ITEM_REMOVED = 'item_removed';
    const ACTION_ITEM_MODIFIED = 'item_modified';
    const ACTION_PAYMENT_ADDED = 'payment_added';
    const ACTION_PAYMENT_UPDATED = 'payment_updated';
    const ACTION_TABLE_CHANGED = 'table_changed';
    const ACTION_MERGED = 'merged';
    const ACTION_KOT_PRINTED = 'kot_printed';
    const ACTION_DISCOUNT_APPLIED = 'discount_applied';
    const ACTION_RESTORED = 'restored';

    /**
     * Get the order this log belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Create a log entry for an order.
     *
     * @param Order $order
     * @param string $action
     * @param array $options [reason, old_status, new_status, old_values, new_values, description]
     * @return OrderLog
     */
    public static function logAction(Order $order, string $action, array $options = []): OrderLog
    {
        return static::create([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'action' => $action,
            'old_status' => $options['old_status'] ?? null,
            'new_status' => $options['new_status'] ?? null,
            'reason' => $options['reason'] ?? null,
            'old_values' => $options['old_values'] ?? null,
            'new_values' => $options['new_values'] ?? null,
            'description' => $options['description'] ?? static::generateDescription($action, $order, $options),
            'performed_by' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log order creation.
     */
    public static function logCreated(Order $order, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_CREATED, [
            'new_status' => $order->status,
            'reason' => $reason,
            'new_values' => $order->toArray(),
        ]);
    }

    /**
     * Log status change.
     */
    public static function logStatusChanged(Order $order, string $oldStatus, string $newStatus, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_STATUS_CHANGED, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
        ]);
    }

    /**
     * Log order update.
     */
    public static function logUpdated(Order $order, array $oldValues, array $newValues, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_UPDATED, [
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
        ]);
    }

    /**
     * Log order deletion.
     */
    public static function logDeleted(Order $order, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_DELETED, [
            'old_status' => $order->status,
            'new_status' => 'deleted',
            'reason' => $reason,
            'old_values' => $order->toArray(),
        ]);
    }

    /**
     * Log item added to order.
     */
    public static function logItemAdded(Order $order, $item, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_ITEM_ADDED, [
            'reason' => $reason,
            'new_values' => is_array($item) ? $item : $item->toArray(),
            'description' => "Item added to order",
        ]);
    }

    /**
     * Log item removed from order.
     */
    public static function logItemRemoved(Order $order, $item, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_ITEM_REMOVED, [
            'reason' => $reason,
            'old_values' => is_array($item) ? $item : $item->toArray(),
            'description' => "Item removed from order",
        ]);
    }

    /**
     * Log item modification.
     */
    public static function logItemModified(Order $order, array $oldValues, array $newValues, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_ITEM_MODIFIED, [
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
        ]);
    }

    /**
     * Log table change/transfer.
     */
    public static function logTableChanged(Order $order, $oldTableId, $newTableId, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_TABLE_CHANGED, [
            'old_values' => ['table_id' => $oldTableId],
            'new_values' => ['table_id' => $newTableId],
            'reason' => $reason,
            'description' => "Order transferred from table {$oldTableId} to table {$newTableId}",
        ]);
    }

    /**
     * Log order merge.
     */
    public static function logMerged(Order $order, $mergedFromOrderId, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_MERGED, [
            'old_values' => ['merged_from_order_id' => $mergedFromOrderId],
            'reason' => $reason,
            'description' => "Order merged from order ID: {$mergedFromOrderId}",
        ]);
    }

    /**
     * Log KOT printed.
     */
    public static function logKotPrinted(Order $order, ?string $kotNumber = null): OrderLog
    {
        return static::logAction($order, self::ACTION_KOT_PRINTED, [
            'new_values' => ['kot_number' => $kotNumber],
            'description' => "KOT printed" . ($kotNumber ? ": {$kotNumber}" : ""),
        ]);
    }

    /**
     * Log discount applied.
     */
    public static function logDiscountApplied(Order $order, $discountAmount, $discountType, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_DISCOUNT_APPLIED, [
            'new_values' => [
                'discount_amount' => $discountAmount,
                'discount_type' => $discountType,
            ],
            'reason' => $reason,
        ]);
    }

    /**
     * Log payment added.
     */
    public static function logPaymentAdded(Order $order, array $paymentData, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_PAYMENT_ADDED, [
            'new_values' => $paymentData,
            'reason' => $reason,
        ]);
    }

    /**
     * Log order restored.
     */
    public static function logRestored(Order $order, ?string $reason = null): OrderLog
    {
        return static::logAction($order, self::ACTION_RESTORED, [
            'new_status' => $order->status,
            'reason' => $reason,
        ]);
    }

    /**
     * Generate a human-readable description.
     */
    protected static function generateDescription(string $action, Order $order, array $options): string
    {
        $descriptions = [
            self::ACTION_CREATED => "Order {$order->order_number} was created",
            self::ACTION_STATUS_CHANGED => "Order status changed from " . ($options['old_status'] ?? 'unknown') . " to " . ($options['new_status'] ?? 'unknown'),
            self::ACTION_UPDATED => "Order {$order->order_number} was updated",
            self::ACTION_DELETED => "Order {$order->order_number} was deleted",
            self::ACTION_ITEM_ADDED => "Item was added to order {$order->order_number}",
            self::ACTION_ITEM_REMOVED => "Item was removed from order {$order->order_number}",
            self::ACTION_ITEM_MODIFIED => "Item was modified in order {$order->order_number}",
            self::ACTION_PAYMENT_ADDED => "Payment was added to order {$order->order_number}",
            self::ACTION_PAYMENT_UPDATED => "Payment was updated for order {$order->order_number}",
            self::ACTION_TABLE_CHANGED => "Table was changed for order {$order->order_number}",
            self::ACTION_MERGED => "Order was merged into {$order->order_number}",
            self::ACTION_KOT_PRINTED => "KOT was printed for order {$order->order_number}",
            self::ACTION_DISCOUNT_APPLIED => "Discount was applied to order {$order->order_number}",
            self::ACTION_RESTORED => "Order {$order->order_number} was restored",
        ];

        return $descriptions[$action] ?? "Action '{$action}' performed on order {$order->order_number}";
    }

    /**
     * Get formatted action name.
     */
    public function getActionLabelAttribute(): string
    {
        $labels = [
            self::ACTION_CREATED => 'Created',
            self::ACTION_STATUS_CHANGED => 'Status Changed',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_ITEM_ADDED => 'Item Added',
            self::ACTION_ITEM_REMOVED => 'Item Removed',
            self::ACTION_ITEM_MODIFIED => 'Item Modified',
            self::ACTION_PAYMENT_ADDED => 'Payment Added',
            self::ACTION_PAYMENT_UPDATED => 'Payment Updated',
            self::ACTION_TABLE_CHANGED => 'Table Changed',
            self::ACTION_MERGED => 'Merged',
            self::ACTION_KOT_PRINTED => 'KOT Printed',
            self::ACTION_DISCOUNT_APPLIED => 'Discount Applied',
            self::ACTION_RESTORED => 'Restored',
        ];

        return $labels[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get action badge color class.
     */
    public function getActionColorAttribute(): string
    {
        $colors = [
            self::ACTION_CREATED => 'success',
            self::ACTION_STATUS_CHANGED => 'info',
            self::ACTION_UPDATED => 'primary',
            self::ACTION_DELETED => 'danger',
            self::ACTION_ITEM_ADDED => 'success',
            self::ACTION_ITEM_REMOVED => 'warning',
            self::ACTION_ITEM_MODIFIED => 'primary',
            self::ACTION_PAYMENT_ADDED => 'success',
            self::ACTION_PAYMENT_UPDATED => 'info',
            self::ACTION_TABLE_CHANGED => 'secondary',
            self::ACTION_MERGED => 'info',
            self::ACTION_KOT_PRINTED => 'primary',
            self::ACTION_DISCOUNT_APPLIED => 'warning',
            self::ACTION_RESTORED => 'success',
        ];

        return $colors[$this->action] ?? 'secondary';
    }

    /**
     * Scope to filter by order.
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for today's logs.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
