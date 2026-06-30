<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'item_portion_id',
        'previous_price',
        'new_price',
        'updated_by',
    ];

    protected $casts = [
        'previous_price' => 'decimal:2',
        'new_price' => 'decimal:2',
    ];

    /**
     * Get the item associated with this activity.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withoutGlobalScope('active');
    }

    /**
     * Get the portion/modifier associated with this activity.
     */
    public function portion(): BelongsTo
    {
        return $this->belongsTo(ItemModifier::class, 'item_portion_id')->withoutGlobalScope('active');
    }

    /**
     * Get the user who performed the update.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
