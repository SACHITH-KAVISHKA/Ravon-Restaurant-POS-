<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kot extends Model
{
    use HasFactory;

    protected $fillable = [
        'kot_number',
        'sub_number',
        'order_id',
        'kitchen_station_id',
        'table_id',
        'waiter_id',
        'status',
        'printed_at',
        'print_count',
        'completed_at',
    ];

    protected $casts = [
        'print_count' => 'integer',
        'sub_number' => 'integer',
        'printed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot method to generate KOT number.
     * For sub-KOTs (additions to existing order), uses the SAME base kot_number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kot) {
            if (empty($kot->kot_number)) {
                // Determine if this is a BOT (Bar Order Ticket) based on kitchen_station_id
                $isBar = $kot->kitchen_station_id == 2;

                // Check if there's an existing KOT for this order and station
                // If yes, use the same kot_number (this is a sub-KOT/addition)
                $existingKotNumber = static::getExistingKotNumber($kot->order_id, $kot->kitchen_station_id);

                if ($existingKotNumber) {
                    // Use the same base KOT number for additions
                    $kot->kot_number = $existingKotNumber;
                } else {
                    // Generate a new KOT number for the first KOT
                    $kot->kot_number = static::generateKotNumber($isBar);
                }
            }
        });
    }

    /**
     * Get the existing KOT number for an order and station (if any).
     * Returns the kot_number of the first (primary) KOT, or null if none exists.
     */
    public static function getExistingKotNumber(int $orderId, int $stationId): ?string
    {
        $firstKot = static::where('order_id', $orderId)
            ->where('kitchen_station_id', $stationId)
            ->where('sub_number', 0) // Get the primary KOT
            ->first();

        return $firstKot ? $firstKot->kot_number : null;
    }

    /**
     * Generate unique KOT/BOT number with separate sequences.
     */
    public static function generateKotNumber(bool $isBar = false): string
    {
        $prefix = $isBar ? 'BOT' : 'KOT';
        $date = now()->format('Ymd');

        // Count only unique kot_numbers (not counting sub-KOTs which share the same number)
        // We count distinct kot_numbers to get the proper sequence
        $count = static::whereDate('created_at', today())
            ->where('kot_number', 'like', $prefix . '-%')
            ->where('sub_number', 0) // Only count primary KOTs for proper sequencing
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    /**
     * Get the next sub-number for this order and station.
     * Returns 0 for primary KOT/BOT, 1+ for subsequent additions.
     */
    public static function getNextSubNumber(int $orderId, int $stationId): int
    {
        $maxSubNumber = static::where('order_id', $orderId)
            ->where('kitchen_station_id', $stationId)
            ->max('sub_number');

        return ($maxSubNumber === null) ? 0 : ($maxSubNumber + 1);
    }

    /**
     * Check if this is a primary KOT/BOT (first items for the order).
     */
    public function isPrimary(): bool
    {
        return $this->sub_number === 0;
    }

    /**
     * Get the display number including sub-number if applicable.
     * Format: KOT-YYYYMMDD-XXXX or KOT-YYYYMMDD-XXXX-N (for sub-KOTs)
     */
    public function getDisplayNumber(): string
    {
        if ($this->sub_number > 0) {
            return $this->kot_number . '-' . $this->sub_number;
        }
        return $this->kot_number;
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the kitchen station.
     */
    public function kitchenStation(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class);
    }

    /**
     * Get the table.
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /**
     * Get the waiter.
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    /**
     * Get KOT items.
     */
    public function kotItems(): HasMany
    {
        return $this->hasMany(KotItem::class);
    }

    /**
     * Scope for pending KOTs.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for preparing KOTs.
     */
    public function scopePreparing($query)
    {
        return $query->where('status', 'preparing');
    }

    /**
     * Scope for today's KOTs.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Mark as printed.
     */
    public function markAsPrinted(): void
    {
        $this->printed_at = now();
        $this->print_count++;
        $this->save();
    }
}
