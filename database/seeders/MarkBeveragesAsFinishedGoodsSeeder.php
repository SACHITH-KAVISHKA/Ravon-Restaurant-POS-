<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class MarkBeveragesAsFinishedGoodsSeeder extends Seeder
{
    /**
     * Mark all Beverages items as Finished Goods.
     */
    public function run(): void
    {
        // Beverages category ID is 41
        $count = Item::where('category_id', 41)->update(['is_finished_goods' => true]);

        $this->command->info("Updated {$count} Beverage items to Finished Goods");
    }
}
