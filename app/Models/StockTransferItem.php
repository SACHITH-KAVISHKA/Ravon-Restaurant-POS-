<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'main_stock_item_id',
        'item_name',
        'quantity',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    /**
     * Get the parent transfer.
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    /**
     * Get the main stock item.
     */
    public function mainStockItem(): BelongsTo
    {
        return $this->belongsTo(MainStockItem::class);
    }
}
