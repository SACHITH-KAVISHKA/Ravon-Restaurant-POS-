<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemModifier;
use App\Models\KitchenStation;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainKitchen = KitchenStation::where('name', 'Main Kitchen')->first();
        $bar = KitchenStation::where('name', 'Bar')->first();
        $grill = KitchenStation::where('name', 'Grill Station')->first();
        $pastry = KitchenStation::where('name', 'Pastry')->first();

        // FOOD Category
        $food = Category::create([
            'name' => 'FOOD',
            'slug' => 'food',
            'description' => 'Main food items',
            'display_order' => 1,
            'is_active' => true,
        ]);

        // Rice Items
        $items = [
            ['name' => 'Chicken Fried Rice', 'price' => 850.00, 'station' => $mainKitchen],
            ['name' => 'Seafood Fried Rice', 'price' => 950.00, 'station' => $mainKitchen],
            ['name' => 'Mixed Fried Rice', 'price' => 900.00, 'station' => $mainKitchen],
            ['name' => 'Vegetable Fried Rice', 'price' => 650.00, 'station' => $mainKitchen],
        ];

        foreach ($items as $itemData) {
            $item = Item::create([
                'category_id' => $food->id,
                'kitchen_station_id' => $itemData['station']->id,
                'name' => $itemData['name'],
                'slug' => \Str::slug($itemData['name']),
                'price' => $itemData['price'],
                'cost_price' => $itemData['price'] * 0.4,
                'preparation_time' => 15,
                'is_available' => true,
                'is_featured' => false,
            ]);

            // Add modifiers
            ItemModifier::create([
                'item_id' => $item->id,
                'name' => 'Extra Spicy',
                'type' => 'level',
                'price_adjustment' => 0.00,
            ]);

            ItemModifier::create([
                'item_id' => $item->id,
                'name' => 'No Spicy',
                'type' => 'level',
                'price_adjustment' => 0.00,
            ]);

            ItemModifier::create([
                'item_id' => $item->id,
                'name' => 'Add Egg',
                'type' => 'addon',
                'price_adjustment' => 50.00,
            ]);
        }

        // Noodles
        $noodleItems = [
            ['name' => 'Chicken Noodles', 'price' => 800.00],
            ['name' => 'Seafood Noodles', 'price' => 900.00],
            ['name' => 'Mixed Noodles', 'price' => 850.00],
        ];

        foreach ($noodleItems as $itemData) {
            Item::create([
                'category_id' => $food->id,
                'kitchen_station_id' => $mainKitchen->id,
                'name' => $itemData['name'],
                'slug' => \Str::slug($itemData['name']),
                'price' => $itemData['price'],
                'cost_price' => $itemData['price'] * 0.4,
                'preparation_time' => 12,
                'is_available' => true,
            ]);
        }

        // Grill Items
        $grillCategory = Category::create([
            'name' => 'GRILL',
            'slug' => 'grill',
            'description' => 'Grilled items',
            'display_order' => 2,
            'is_active' => true,
        ]);

        $grillItems = [
            ['name' => 'Grilled Chicken', 'price' => 1200.00],
            ['name' => 'Grilled Fish', 'price' => 1500.00],
            ['name' => 'BBQ Pork Ribs', 'price' => 1800.00],
        ];

        foreach ($grillItems as $itemData) {
            Item::create([
                'category_id' => $grillCategory->id,
                'kitchen_station_id' => $grill->id,
                'name' => $itemData['name'],
                'slug' => \Str::slug($itemData['name']),
                'price' => $itemData['price'],
                'cost_price' => $itemData['price'] * 0.45,
                'preparation_time' => 25,
                'is_available' => true,
                'is_featured' => true,
            ]);
        }

        // BEVERAGES Category
        $beverages = Category::create([
            'name' => 'BEVERAGES',
            'slug' => 'beverages',
            'description' => 'Drinks and beverages',
            'display_order' => 3,
            'is_active' => true,
        ]);

        $drinks = [
            ['name' => 'Fresh Orange Juice', 'price' => 350.00],
            ['name' => 'Mango Juice', 'price' => 350.00],
            ['name' => 'Lime Juice', 'price' => 250.00],
            ['name' => 'Coca Cola', 'price' => 200.00],
            ['name' => 'Sprite', 'price' => 200.00],
        ];

        foreach ($drinks as $itemData) {
            Item::create([
                'category_id' => $beverages->id,
                'kitchen_station_id' => $bar->id,
                'name' => $itemData['name'],
                'slug' => \Str::slug($itemData['name']),
                'price' => $itemData['price'],
                'cost_price' => $itemData['price'] * 0.3,
                'preparation_time' => 5,
                'is_available' => true,
            ]);
        }

        // DESSERTS Category
        $desserts = Category::create([
            'name' => 'DESSERTS',
            'slug' => 'desserts',
            'description' => 'Sweet treats',
            'display_order' => 4,
            'is_active' => true,
        ]);

        $dessertItems = [
            ['name' => 'Chocolate Cake', 'price' => 450.00],
            ['name' => 'Cheese Cake', 'price' => 550.00],
            ['name' => 'Ice Cream Sundae', 'price' => 350.00],
        ];

        foreach ($dessertItems as $itemData) {
            Item::create([
                'category_id' => $desserts->id,
                'kitchen_station_id' => $pastry->id,
                'name' => $itemData['name'],
                'slug' => \Str::slug($itemData['name']),
                'price' => $itemData['price'],
                'cost_price' => $itemData['price'] * 0.35,
                'preparation_time' => 8,
                'is_available' => true,
            ]);
        }

        // PROMOTIONS Category
        $promotions = Category::create([
            'name' => 'PROMOTIONS',
            'slug' => 'promotions',
            'description' => 'Special offers',
            'display_order' => 5,
            'is_active' => true,
        ]);
    }
}
