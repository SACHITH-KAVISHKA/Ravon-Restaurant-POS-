<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'table_number',
        'capacity',
        'status',
        'current_order_id',
        'position_x',
        'position_y',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the floor.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the current order.
     */
    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    /**
     * Get all orders.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get table merges where this is master table.
     */
    public function masterMerges(): HasMany
    {
        return $this->hasMany(TableMerge::class, 'master_table_id');
    }

    /**
     * Get table merges where this table was merged.
     */
    public function mergedInto(): HasMany
    {
        return $this->hasMany(TableMerge::class, 'merged_table_id');
    }

    /**
     * Scope to get available tables.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to get occupied tables.
     */
    public function scopeOccupied($query)
    {
        return $query->whereIn('status', ['ordered', 'serving', 'bill_requested']);
    }

    /**
     * Scope to get by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active tables.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by floor.
     */
    public function scopeOnFloor($query, $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    /**
     * Check if table is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if table is occupied.
     */
    public function isOccupied(): bool
    {
        return in_array($this->status, ['ordered', 'serving', 'bill_requested']);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'available' => 'green',
            'ordered' => 'orange',
            'serving' => 'red',
            'bill_requested' => 'blue',
            default => 'gray',
        };
    }
}
