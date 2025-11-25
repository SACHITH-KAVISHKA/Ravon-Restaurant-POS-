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
                $kot->kot_number = static::generateKotNumber();
            }
        });
    }

    /**
     * Generate unique KOT number.
     */
    public static function generateKotNumber(): string
    {
        $prefix = 'KOT';
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
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
