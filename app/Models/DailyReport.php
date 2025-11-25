<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'total_orders',
        'total_sales',
        'total_tax',
        'total_discounts',
        'cash_sales',
        'card_sales',
        'cancelled_orders',
        'dine_in_orders',
        'takeaway_orders',
        'delivery_orders',
        'generated_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_orders' => 'integer',
        'total_sales' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_discounts' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'card_sales' => 'decimal:2',
        'cancelled_orders' => 'integer',
        'dine_in_orders' => 'integer',
        'takeaway_orders' => 'integer',
        'delivery_orders' => 'integer',
        'generated_at' => 'datetime',
    ];

    /**
     * Generate report for a specific date.
     */
    public static function generateForDate($date)
    {
        $orders = Order::whereDate('created_at', $date)->get();
        
        return static::create([
            'report_date' => $date,
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_amount'),
            'total_tax' => $orders->sum('tax_amount'),
            'total_discounts' => $orders->sum('discount_amount'),
            'cash_sales' => Payment::whereDate('processed_at', $date)->cash()->sum('paid_amount'),
            'card_sales' => Payment::whereDate('processed_at', $date)->card()->sum('paid_amount'),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'dine_in_orders' => $orders->where('order_type', 'dine_in')->count(),
            'takeaway_orders' => $orders->where('order_type', 'takeaway')->count(),
            'delivery_orders' => $orders->whereIn('order_type', ['delivery', 'uber_eats', 'pickme'])->count(),
            'generated_at' => now(),
        ]);
    }
}
