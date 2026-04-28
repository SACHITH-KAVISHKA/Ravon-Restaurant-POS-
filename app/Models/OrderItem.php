<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'item_id',
        'item_modifier_id',
        'item_display_name',
        'quantity',
        'latest_added_quantity',
        'delivered_quantity',
        'unit_price',
        'subtotal',
        'status',
        'preparing_at',
        'delivered_at',
        'last_delivered_at',
        'special_instructions',
        'excluded_ingredients',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'latest_added_quantity' => 'integer',
        'delivered_quantity' => 'integer',
        'item_modifier_id' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'preparing_at' => 'datetime',
        'delivered_at' => 'datetime',
        'last_delivered_at' => 'datetime',
        'excluded_ingredients' => 'array',
    ];

    /**
     * Boot method to calculate subtotal.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            $modifiersTotal = $orderItem->modifiers->sum('price_adjustment');
            $orderItem->subtotal = ($orderItem->unit_price + $modifiersTotal) * $orderItem->quantity;

            $quantity = max((int) $orderItem->quantity, 0);
            $deliveredQuantity = max((int) $orderItem->delivered_quantity, 0);
            $originalQuantity = max((int) $orderItem->getOriginal('quantity'), 0);
            $originalDeliveredQuantity = max((int) $orderItem->getOriginal('delivered_quantity'), 0);
            $originalDeliveredAt = $orderItem->getOriginal('delivered_at');
            $originalLastDeliveredAt = $orderItem->getOriginal('last_delivered_at');

            if ($deliveredQuantity > $quantity) {
                $deliveredQuantity = $quantity;
                $orderItem->delivered_quantity = $deliveredQuantity;
            }

            if ($orderItem->status === 'delivered' && $deliveredQuantity !== $quantity) {
                $orderItem->status = 'preparing';
                $orderItem->delivered_at = null;
            }

            if ($orderItem->status === 'preparing' && $quantity > 0 && $deliveredQuantity === $quantity) {
                $orderItem->status = 'delivered';
            }

            $orderItem->delivered_at = $originalDeliveredAt ?? $orderItem->delivered_at;

            $isFullyDelivered = $quantity > 0 && $deliveredQuantity === $quantity;
            $wasFullyDelivered = $originalQuantity > 0 && $originalDeliveredQuantity === $originalQuantity;

            if ($isFullyDelivered) {
                $needsRecalculation = ! $wasFullyDelivered
                    || $originalQuantity !== $quantity
                    || $originalDeliveredQuantity !== $deliveredQuantity
                    || $originalLastDeliveredAt === null;

                $orderItem->last_delivered_at = $needsRecalculation
                    ? now()
                    : $originalLastDeliveredAt;
            } else {
                $orderItem->last_delivered_at = null;
            }
        });
    }

    /**
     * Build tracking attributes for a newly created order item.
     */
    public function initialTrackingAttributes(): array
    {
        return [
            'latest_added_quantity' => (int) $this->quantity,
            'delivered_quantity' => 0,
            'preparing_at' => now(),
            'delivered_at' => null,
            'last_delivered_at' => null,
        ];
    }

    /**
     * Build tracking attributes after a quantity increase.
     */
    public function quantityIncreaseTrackingAttributes(int $newQuantity): array
    {
        $currentQuantity = (int) $this->quantity;
        $addedQuantity = max($newQuantity - $currentQuantity, 0);
        $deliveredQuantity = min((int) $this->delivered_quantity, $newQuantity);

        $attributes = [
            'latest_added_quantity' => $addedQuantity,
            'delivered_quantity' => $deliveredQuantity,
        ];

        if ($addedQuantity > 0) {
            $attributes['preparing_at'] = now();
            $attributes['last_delivered_at'] = null;
        }

        $attributes['delivered_at'] = $this->delivered_at;

        return $attributes;
    }

    /**
     * Build tracking attributes after a quantity decrease.
     */
    public function quantityDecreaseTrackingAttributes(int $newQuantity): array
    {
        $deliveredQuantity = min((int) $this->delivered_quantity, $newQuantity);
        $isFullyDelivered = $newQuantity > 0 && $deliveredQuantity === $newQuantity;

        return [
            'latest_added_quantity' => 0,
            'delivered_quantity' => $deliveredQuantity,
            'delivered_at' => $this->delivered_at,
            'last_delivered_at' => $isFullyDelivered ? now() : null,
        ];
    }

    /**
     * Build tracking attributes for a partial or full delivery.
     */
    public function deliveryTrackingAttributes(int $deliveredAmount): array
    {
        $remainingQuantity = max((int) $this->quantity - (int) $this->delivered_quantity, 0);
        $deliveredAmount = min(max($deliveredAmount, 0), $remainingQuantity);
        $deliveredQuantity = (int) $this->delivered_quantity + $deliveredAmount;
        $isFirstDelivery = (int) $this->delivered_quantity === 0 && $deliveredQuantity > 0;
        $isFullyDelivered = (int) $this->quantity > 0 && $deliveredQuantity === (int) $this->quantity;

        $attributes = [
            'delivered_quantity' => $deliveredQuantity,
            'delivered_at' => $isFirstDelivery ? now() : $this->delivered_at,
            'last_delivered_at' => $isFullyDelivered ? ($this->last_delivered_at ?? now()) : null,
        ];

        return $attributes;
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the item modifier/portion (for ID-based stock deduction).
     */
    public function itemModifier(): BelongsTo
    {
        return $this->belongsTo(ItemModifier::class, 'item_modifier_id');
    }

    /**
     * Get the modifiers.
     */
    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class);
    }

    /**
     * Get KOT items.
     */
    public function kotItems(): HasMany
    {
        return $this->hasMany(KotItem::class);
    }

    /**
     * Scope for pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for preparing items.
     */
    public function scopePreparing($query)
    {
        return $query->where('status', 'preparing');
    }

    /**
     * Scope for ready items.
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    /**
     * Scope for active (non-deleted) items.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'deleted');
    }

    /**
     * Scope for deleted items.
     */
    public function scopeDeleted($query)
    {
        return $query->where('status', 'deleted');
    }
}
