<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update prices for finished goods from POS items table
        $finishedGoods = \App\Models\MainStockItem::where('item_type', 'finished_good')
            ->whereNotNull('linked_item_id')
            ->get();

        foreach ($finishedGoods as $stockItem) {
            $price = null;

            // Get the linked POS item
            $posItem = \App\Models\Item::find($stockItem->linked_item_id);

            if ($posItem) {
                if ($stockItem->linked_item_modifier_id) {
                    // If there's a modifier/portion, get the modifier price
                    $modifier = \App\Models\ItemModifier::find($stockItem->linked_item_modifier_id);
                    $price = $modifier ? $modifier->getPriceByType('default') : $posItem->getPriceByType('default');
                } else {
                    // No modifier, use item price
                    $price = $posItem->getPriceByType('default');
                }

                // Update the stock item price
                $stockItem->update(['price' => $price]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally clear prices for finished goods
        \App\Models\MainStockItem::where('item_type', 'finished_good')
            ->update(['price' => null]);
    }
};
