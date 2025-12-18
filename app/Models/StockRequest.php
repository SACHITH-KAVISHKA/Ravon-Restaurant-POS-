<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'supervisor_id',
        'request_number',
        'status',
        'cashier_response',
        'notes',
        'supervisor_notes',
        'cashier_response_notes',
        'responded_at',
        'cashier_responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'cashier_responded_at' => 'datetime',
    ];

    /**
     * Boot method to generate request number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($stockRequest) {
            if (empty($stockRequest->request_number)) {
                $stockRequest->request_number = 'SR-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the cashier who made the request.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the supervisor who handled the request.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the request items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockRequestItem::class);
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted requests.
     */
    public function scopeAccepted($query)
    {
        return $query->whereIn('status', ['accepted', 'partially_accepted']);
    }

    /**
     * Scope to get rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to filter by cashier.
     */
    public function scopeByCashier($query, $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'accepted' => 'green',
            'rejected' => 'red',
            'partially_accepted' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get cashier response badge color.
     */
    public function getCashierResponseColorAttribute(): string
    {
        return match ($this->cashier_response) {
            'pending' => 'yellow',
            'accepted' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
