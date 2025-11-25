<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableMerge extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_table_id',
        'merged_table_id',
        'order_id',
        'merged_by',
        'merged_at',
        'unmerged_at',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
        'unmerged_at' => 'datetime',
    ];

    /**
     * Get the master table.
     */
    public function masterTable(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'master_table_id');
    }

    /**
     * Get the merged table.
     */
    public function mergedTable(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'merged_table_id');
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get who merged the tables.
     */
    public function merger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    /**
     * Scope for active merges.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('unmerged_at');
    }
}
