<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MainStockTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_stock_transaction_id',
        'main_stock_item_id',
        'quantity',
        'quantity_before',
        'quantity_after',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
    ];

    /**
     * Get the parent transaction.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MainStockTransaction::class, 'main_stock_transaction_id');
    }

    /**
     * Get the stock item.
     */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class, 'main_stock_item_id');
    }
}
