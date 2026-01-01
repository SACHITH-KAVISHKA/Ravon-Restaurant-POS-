<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MainStockItem;
use App\Models\User;

class MainStockItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Populates MainStockItems with raw materials and finished goods.
     */
    public function run(): void
    {
        $supervisorUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'supervisor');
        })->first();

        $userId = $supervisorUser ? $supervisorUser->id : 1;

        // Raw Materials
        $rawMaterials = [
            // Vegetables
            ['name' => 'Tomatoes', 'unit' => 'kilogram', 'qty' => 50, 'min' => 10],
            ['name' => 'Onions', 'unit' => 'kilogram', 'qty' => 40, 'min' => 10],
            ['name' => 'Potatoes', 'unit' => 'kilogram', 'qty' => 60, 'min' => 15],
            ['name' => 'Carrots', 'unit' => 'kilogram', 'qty' => 30, 'min' => 8],
            ['name' => 'Cabbage', 'unit' => 'kilogram', 'qty' => 25, 'min' => 5],
            ['name' => 'Lettuce', 'unit' => 'kilogram', 'qty' => 15, 'min' => 5],
            ['name' => 'Bell Peppers', 'unit' => 'kilogram', 'qty' => 20, 'min' => 5],
            ['name' => 'Garlic', 'unit' => 'kilogram', 'qty' => 10, 'min' => 3],
            ['name' => 'Ginger', 'unit' => 'kilogram', 'qty' => 8, 'min' => 2],
            ['name' => 'Green Chilies', 'unit' => 'kilogram', 'qty' => 5, 'min' => 2],

            // Proteins
            ['name' => 'Chicken Breast', 'unit' => 'kilogram', 'qty' => 30, 'min' => 10],
            ['name' => 'Chicken Legs', 'unit' => 'kilogram', 'qty' => 25, 'min' => 8],
            ['name' => 'Beef', 'unit' => 'kilogram', 'qty' => 20, 'min' => 5],
            ['name' => 'Fish Fillet', 'unit' => 'kilogram', 'qty' => 15, 'min' => 5],
            ['name' => 'Prawns', 'unit' => 'kilogram', 'qty' => 10, 'min' => 3],
            ['name' => 'Eggs', 'unit' => 'quantity', 'qty' => 200, 'min' => 50],

            // Dairy
            ['name' => 'Milk', 'unit' => 'liter', 'qty' => 50, 'min' => 15],
            ['name' => 'Cream', 'unit' => 'liter', 'qty' => 20, 'min' => 5],
            ['name' => 'Butter', 'unit' => 'kilogram', 'qty' => 15, 'min' => 5],
            ['name' => 'Cheese', 'unit' => 'kilogram', 'qty' => 10, 'min' => 3],
            ['name' => 'Yogurt', 'unit' => 'kilogram', 'qty' => 20, 'min' => 5],

            // Grains & Flour
            ['name' => 'Rice', 'unit' => 'kilogram', 'qty' => 100, 'min' => 25],
            ['name' => 'Wheat Flour', 'unit' => 'kilogram', 'qty' => 80, 'min' => 20],
            ['name' => 'All Purpose Flour', 'unit' => 'kilogram', 'qty' => 50, 'min' => 15],
            ['name' => 'Bread Crumbs', 'unit' => 'kilogram', 'qty' => 10, 'min' => 3],
            ['name' => 'Pasta', 'unit' => 'kilogram', 'qty' => 30, 'min' => 10],

            // Oils & Condiments
            ['name' => 'Cooking Oil', 'unit' => 'liter', 'qty' => 40, 'min' => 10],
            ['name' => 'Olive Oil', 'unit' => 'liter', 'qty' => 10, 'min' => 3],
            ['name' => 'Soy Sauce', 'unit' => 'liter', 'qty' => 8, 'min' => 2],
            ['name' => 'Tomato Ketchup', 'unit' => 'kilogram', 'qty' => 15, 'min' => 5],
            ['name' => 'Mayonnaise', 'unit' => 'kilogram', 'qty' => 10, 'min' => 3],
            ['name' => 'Vinegar', 'unit' => 'liter', 'qty' => 10, 'min' => 3],

            // Spices & Seasonings
            ['name' => 'Salt', 'unit' => 'kilogram', 'qty' => 25, 'min' => 10],
            ['name' => 'Black Pepper', 'unit' => 'kilogram', 'qty' => 5, 'min' => 2],
            ['name' => 'Cumin Powder', 'unit' => 'kilogram', 'qty' => 3, 'min' => 1],
            ['name' => 'Turmeric Powder', 'unit' => 'kilogram', 'qty' => 3, 'min' => 1],
            ['name' => 'Chili Powder', 'unit' => 'kilogram', 'qty' => 5, 'min' => 2],
            ['name' => 'Coriander Powder', 'unit' => 'kilogram', 'qty' => 3, 'min' => 1],
            ['name' => 'Garam Masala', 'unit' => 'kilogram', 'qty' => 2, 'min' => 0.5],

            // Beverages Base
            ['name' => 'Coffee Beans', 'unit' => 'kilogram', 'qty' => 10, 'min' => 3],
            ['name' => 'Tea Leaves', 'unit' => 'kilogram', 'qty' => 8, 'min' => 2],
            ['name' => 'Sugar', 'unit' => 'kilogram', 'qty' => 50, 'min' => 15],
            ['name' => 'Cocoa Powder', 'unit' => 'kilogram', 'qty' => 5, 'min' => 2],

            // Miscellaneous
            ['name' => 'Bread Loaf', 'unit' => 'quantity', 'qty' => 50, 'min' => 15],
            ['name' => 'Buns', 'unit' => 'quantity', 'qty' => 100, 'min' => 30],
            ['name' => 'Tortilla Wraps', 'unit' => 'quantity', 'qty' => 80, 'min' => 20],
            ['name' => 'Ice Cream', 'unit' => 'liter', 'qty' => 30, 'min' => 10],
        ];

        // Finished Goods (Beverages & Ready Items)
        $finishedGoods = [
            ['name' => 'Coca Cola', 'unit' => 'quantity', 'qty' => 200, 'min' => 50],
            ['name' => 'Sprite', 'unit' => 'quantity', 'qty' => 150, 'min' => 40],
            ['name' => 'Fanta', 'unit' => 'quantity', 'qty' => 150, 'min' => 40],
            ['name' => 'Bottled Water', 'unit' => 'quantity', 'qty' => 300, 'min' => 100],
            ['name' => 'Orange Juice', 'unit' => 'liter', 'qty' => 40, 'min' => 15],
            ['name' => 'Apple Juice', 'unit' => 'liter', 'qty' => 30, 'min' => 10],
            ['name' => 'Mango Juice', 'unit' => 'liter', 'qty' => 35, 'min' => 12],
            ['name' => 'Energy Drink', 'unit' => 'quantity', 'qty' => 80, 'min' => 25],
        ];

        $this->command->info('Seeding Raw Materials...');
        foreach ($rawMaterials as $item) {
            $code = MainStockItem::generateItemCode('raw_material');
            MainStockItem::create([
                'item_code' => $code,
                'item_name' => $item['name'],
                'unit_type' => $item['unit'],
                'item_type' => 'raw_material',
                'quantity' => $item['qty'],
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $this->command->info("✓ {$item['name']} - {$item['qty']} " . MainStockItem::UNIT_ABBREVIATIONS[$item['unit']]);
        }

        $this->command->info('');
        $this->command->info('Seeding Finished Goods...');
        foreach ($finishedGoods as $item) {
            $code = MainStockItem::generateItemCode('finished_good');
            MainStockItem::create([
                'item_code' => $code,
                'item_name' => $item['name'],
                'unit_type' => $item['unit'],
                'item_type' => 'finished_good',
                'quantity' => $item['qty'],
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $this->command->info("✓ {$item['name']} - {$item['qty']} " . MainStockItem::UNIT_ABBREVIATIONS[$item['unit']]);
        }

        $this->command->info('');
        $this->command->info('MainStockItem seeding completed!');
        $this->command->info('Total items: ' . MainStockItem::count());
    }
}
