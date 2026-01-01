<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainStockTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'transaction_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_number',
        'notes',
        'performed_by',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'transaction_date' => 'datetime',
    ];

    /**
     * Transaction type labels for display
     */
    public const TRANSACTION_TYPES = [
        'stock_in' => 'Stock In',
        'stock_out' => 'Stock Out',
        'adjustment' => 'Adjustment',
        'transfer_to_restaurant' => 'Transfer to Restaurant',
        'return_from_restaurant' => 'Return from Restaurant',
        'wastage' => 'Wastage',
        'expired' => 'Expired',
    ];

    /**
     * Transaction type colors for badges
     */
    public const TRANSACTION_TYPE_COLORS = [
        'stock_in' => 'bg-green-100 text-green-700',
        'stock_out' => 'bg-red-100 text-red-700',
        'adjustment' => 'bg-blue-100 text-blue-700',
        'transfer_to_restaurant' => 'bg-purple-100 text-purple-700',
        'return_from_restaurant' => 'bg-indigo-100 text-indigo-700',
        'wastage' => 'bg-orange-100 text-orange-700',
        'expired' => 'bg-gray-100 text-gray-700',
    ];

    /**
     * Boot the model and auto-generate transaction number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateTransactionNumber();
            }
            if (empty($transaction->transaction_date)) {
                $transaction->transaction_date = now();
            }
        });
    }

    /**
     * Get the main stock item.
     */
    public function mainStockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class);
    }

    /**
     * Get the transaction items (for bulk transactions).
     */
    public function items(): HasMany
    {
        return $this->hasMany(MainStockTransactionItem::class);
    }

    /**
     * Get the user who performed the transaction.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get transaction type label.
     */
    public function getTransactionTypeLabelAttribute(): string
    {
        return self::TRANSACTION_TYPES[$this->transaction_type] ?? $this->transaction_type;
    }

    /**
     * Get transaction type badge color.
     */
    public function getTransactionTypeColorAttribute(): string
    {
        return self::TRANSACTION_TYPE_COLORS[$this->transaction_type] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * Check if transaction increased stock.
     */
    public function isStockIncrease(): bool
    {
        return in_array($this->transaction_type, ['stock_in', 'return_from_restaurant', 'adjustment'])
            && $this->quantity_after > $this->quantity_before;
    }

    /**
     * Check if transaction decreased stock.
     */
    public function isStockDecrease(): bool
    {
        return in_array($this->transaction_type, ['stock_out', 'transfer_to_restaurant', 'wastage', 'expired'])
            || ($this->transaction_type === 'adjustment' && $this->quantity_after < $this->quantity_before);
    }

    /**
     * Get the change amount (positive or negative).
     */
    public function getChangeAmountAttribute(): float
    {
        return $this->quantity_after - $this->quantity_before;
    }

    /**
     * Generate unique transaction number.
     */
    public static function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'TXN' . $date;

        $lastTransaction = self::where('transaction_number', 'like', $prefix . '%')
            ->orderBy('transaction_number', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for transactions by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for transactions within a date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('transaction_date', [$start, $end]);
    }

    /**
     * Scope for today's transactions.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('transaction_date', today());
    }
}
