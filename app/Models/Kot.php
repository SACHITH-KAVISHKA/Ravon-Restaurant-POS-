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
        'printed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot method to generate KOT number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kot) {
            if (empty($kot->kot_number)) {
                // Determine if this is a BOT (Bar Order Ticket) based on kitchen_station_id
                // Kitchen station 2 is typically the bar
                $isBar = $kot->kitchen_station_id == 2;
                $kot->kot_number = static::generateKotNumber($isBar);
            }
        });
    }

    /**
     * Generate unique KOT/BOT number with separate sequences.
     */
    public static function generateKotNumber(bool $isBar = false): string
    {
        $prefix = $isBar ? 'BOT' : 'KOT';
        $date = now()->format('Ymd');
        
        // Count only tickets of the same type (KOT or BOT) for separate sequences
        $count = static::whereDate('created_at', today())
            ->where('kot_number', 'like', $prefix . '-%')
            ->count() + 1;
            
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
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
