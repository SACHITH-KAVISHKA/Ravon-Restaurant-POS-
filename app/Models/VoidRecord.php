<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoidRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'void_number',
        'order_id',
        'order_number',
        'item_id',
        'order_item_id',
        'item_name',
        'item_code',
        'voided_quantity',
        'unit_price',
        'voided_amount',
        'cashier_id',
        'supervisor_id',
        'supervisor_name',
        'reason',
        'table_number',
        'cancel_kot_number',
    ];

    protected $casts = [
        'voided_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'voided_amount' => 'decimal:2',
    ];

    /**
     * Get the order associated with this void record.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the item that was voided.
     */
    public function item()
    {
        return $this->belongsTo(Item::class)->withoutGlobalScope('active');
    }

    /**
     * Get the order item that was voided.
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the cashier who processed the void.
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the supervisor who approved the void.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Generate a unique void number.
     */
    public static function generateVoidNumber(): string
    {
        $today = now()->format('Ymd');
        $lastVoid = self::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastVoid && preg_match('/VOID-' . $today . '-(\d+)/', $lastVoid->void_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = 1;
        }

        return 'VOID-' . $today . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope to get voids by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get voids by supervisor.
     */
    public function scopeBySupervisor($query, $supervisorId)
    {
        return $query->where('supervisor_id', $supervisorId);
    }

    /**
     * Scope to get voids by cashier.
     */
    public function scopeByCashier($query, $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }
}
