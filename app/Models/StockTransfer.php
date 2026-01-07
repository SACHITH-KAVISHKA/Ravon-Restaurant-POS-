<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'supervisor_id',
        'cashier_id',
        'status',
        'notes',
        'cashier_notes',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transfer_number)) {
                $model->transfer_number = self::generateTransferNumber();
            }
        });
    }

    /**
     * Generate a unique transfer number.
     */
    public static function generateTransferNumber(): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $lastTransfer = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the supervisor (creator) of this transfer.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the target cashier.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the transfer items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /**
     * Scope for pending transfers.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for accepted transfers.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for rejected transfers.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for transfers created by a specific supervisor.
     */
    public function scopeBySupervisor($query, int $userId)
    {
        return $query->where('supervisor_id', $userId);
    }
}
