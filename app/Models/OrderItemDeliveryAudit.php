<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemDeliveryAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'order_id',
        'action_type',
        'old_value',
        'new_value',
        'supervisor_id',
        'meta',
    ];

    protected $casts = [
        'old_value' => 'integer',
        'new_value' => 'integer',
        'supervisor_id' => 'integer',
        'meta' => 'array',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
