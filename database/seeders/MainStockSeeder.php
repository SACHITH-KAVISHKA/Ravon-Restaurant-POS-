<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemModifier;
use App\Models\MainStock;

class MainStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Populates MainStock with beverages and desserts items with their portions/sizes.
     */
    public function run(): void
    {
        // Get BEVERAGES category
        $beveragesCategory = Category::where('slug', 'beverages')
            ->orWhere('name', 'BEVERAGES')
            ->orWhere('name', 'Beverages')
            ->first();

        // Get DESSERTS category
        $dessertsCategory = Category::where('slug', 'desserts')
            ->orWhere('name', 'DESSERTS')
            ->orWhere('name', 'Desserts')
            ->first();

        if ($beveragesCategory) {
            $this->seedCategoryStock($beveragesCategory, 'BEVERAGES');
        } else {
            $this->command->warn('BEVERAGES category not found!');
        }

        if ($dessertsCategory) {
            $this->seedCategoryStock($dessertsCategory, 'DESSERTS');
        } else {
            $this->command->warn('DESSERTS category not found!');
        }

        $this->command->info('');
        $this->command->info('MainStock seeding completed!');
        $this->command->info('Total MainStock records: ' . MainStock::count());
    }

    /**
     * Seed stock for items in a category.
     */
    private function seedCategoryStock(Category $category, string $categoryName): void
    {
        $items = Item::where('category_id', $category->id)->get();

        foreach ($items as $item) {
            // Get portion/size modifiers for this item
            $modifiers = ItemModifier::where('item_id', $item->id)
                ->whereIn('type', ['size', 'portion', 'Size', 'Portion'])
                ->where('is_active', true)
                ->get();

            if ($modifiers->count() > 0) {
                // Item has portions/sizes - create stock for each
                foreach ($modifiers as $modifier) {
                    $qty = $this->getDefaultQuantity($item->name, $modifier->name);
                    $unit = $this->getUnit($item->name, $modifier->name);

                    MainStock::updateOrCreate(
                        [
                            'item_id' => $item->id,
                            'item_modifier_id' => $modifier->id
                        ],
                        [
                            'quantity' => $qty,
                            'min_quantity' => max(5, $qty * 0.2),
                            'unit' => $unit,
                            'notes' => "Initial stock for {$item->name} ({$modifier->name})",
                        ]
                    );

                    $this->command->info("Added: {$item->name} ({$modifier->name}) - Qty: {$qty} {$unit}");
                }
            } else {
                // Item has no portions - create single stock record
                $qty = $this->getDefaultQuantity($item->name, null);
                $unit = $this->getUnit($item->name, null);

                MainStock::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'item_modifier_id' => null
                    ],
                    [
                        'quantity' => $qty,
                        'min_quantity' => max(5, $qty * 0.2),
                        'unit' => $unit,
                        'notes' => "Initial stock for {$item->name}",
                    ]
                );

                $this->command->info("Added: {$item->name} - Qty: {$qty} {$unit}");
            }
        }
    }

    /**
     * Get default quantity based on item name and modifier.
     */
    private function getDefaultQuantity(string $itemName, ?string $modifierName): int
    {
        // Beverages - smaller sizes get more stock
        if (str_contains(strtolower($itemName), 'cola') || str_contains(strtolower($itemName), 'sprite')) {
            if ($modifierName) {
                if (str_contains($modifierName, '250')) return 100;
                if (str_contains($modifierName, '500')) return 80;
                if (str_contains($modifierName, '1 L') || str_contains($modifierName, '1L')) return 50;
            }
            return 100;
        }

        if (str_contains(strtolower($itemName), 'juice')) {
            if ($modifierName) {
                if (str_contains($modifierName, '200')) return 60;
                if (str_contains($modifierName, '350')) return 50;
                if (str_contains($modifierName, '500')) return 40;
            }
            return 50;
        }

        if (str_contains(strtolower($itemName), 'water')) {
            if ($modifierName) {
                if (str_contains($modifierName, '330')) return 150;
                if (str_contains($modifierName, '500')) return 100;
                if (str_contains($modifierName, '1.5')) return 50;
            }
            return 150;
        }

        // Desserts
        if (str_contains(strtolower($itemName), 'cake')) {
            return 20;
        }

        if (str_contains(strtolower($itemName), 'ice cream')) {
            return 30;
        }

        return 50; // Default
    }

    /**
     * Get the unit of measurement based on item.
     */
    private function getUnit(string $itemName, ?string $modifierName): string
    {
        if (
            str_contains(strtolower($itemName), 'cola') ||
            str_contains(strtolower($itemName), 'sprite') ||
            str_contains(strtolower($itemName), 'water')
        ) {
            return 'bottle';
        }

        if (str_contains(strtolower($itemName), 'juice')) {
            return 'glass';
        }

        if (str_contains(strtolower($itemName), 'cake')) {
            return 'slice';
        }

        if (str_contains(strtolower($itemName), 'ice cream')) {
            return 'portion';
        }

        return 'pcs';
    }
}
