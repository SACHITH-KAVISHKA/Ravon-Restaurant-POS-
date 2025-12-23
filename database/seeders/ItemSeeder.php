<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing items
        Item::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $items = [
            // === SOUP ITEMS (category_id: 1) ===
            ['category_id' => 1, 'name' => 'Sweet Corn Chicken Soup', 'slug' => 'sweet-corn-chicken-soup', 'description' => 'Sweet corn chicken soup', 'price' => 700, 'display_order' => 0],
            ['category_id' => 1, 'name' => 'Tom Yum Seafood Soup', 'slug' => 'tom-yum-seafood-soup', 'description' => 'Tom yum seafood soup', 'price' => 750, 'display_order' => 1],
            ['category_id' => 1, 'name' => 'Egg & Mushroom Soup', 'slug' => 'egg-mushroom-soup', 'description' => 'Egg and mushroom soup', 'price' => 600, 'display_order' => 2],
            ['category_id' => 1, 'name' => 'Cream Of Vegetable Soup', 'slug' => 'cream-of-vegetable-soup', 'description' => 'Creamy vegetable soup', 'price' => 500, 'display_order' => 3],
            ['category_id' => 1, 'name' => 'Hot & Sour Chicken Soup', 'slug' => 'hot-sour-chicken-soup', 'description' => 'Hot and sour chicken soup', 'price' => 700, 'display_order' => 4],
            ['category_id' => 1, 'name' => 'Prawns Vermicelli Soup', 'slug' => 'prawns-vermicelli-soup', 'description' => 'Prawns vermicelli soup', 'price' => 750, 'display_order' => 5],

            // === SALAD ITEMS (category_id: 2) ===
            ['category_id' => 2, 'name' => 'Coleslaw Salad', 'slug' => 'coleslaw-salad', 'description' => 'Coleslaw salad', 'price' => 600, 'display_order' => 0],
            ['category_id' => 2, 'name' => 'Green Salad', 'slug' => 'green-salad', 'description' => 'Fresh green salad', 'price' => 750, 'display_order' => 1],
            ['category_id' => 2, 'name' => 'Mixed Vegetable Salad', 'slug' => 'mixed-vegetable-salad', 'description' => 'Mixed vegetable salad', 'price' => 650, 'display_order' => 2],
            ['category_id' => 2, 'name' => 'Chicken Salad', 'slug' => 'chicken-salad', 'description' => 'Chicken salad', 'price' => 850, 'display_order' => 3],
            ['category_id' => 2, 'name' => 'Russian Salad', 'slug' => 'russian-salad', 'description' => 'Russian salad', 'price' => 800, 'display_order' => 4],
            ['category_id' => 2, 'name' => 'Creamy Pasta Salad', 'slug' => 'creamy-pasta-salad', 'description' => 'Creamy pasta salad', 'price' => 800, 'display_order' => 5],

            // === OMELETTE ITEMS (category_id: 3) ===
            ['category_id' => 3, 'name' => 'Sri Lankan Spicy Omelette', 'slug' => 'sri-lankan-spicy-omelette', 'description' => 'Sri Lankan spicy omelette', 'price' => 550, 'display_order' => 0],
            ['category_id' => 3, 'name' => 'Chicken Omelette', 'slug' => 'chicken-omelette', 'description' => 'Chicken omelette', 'price' => 700, 'display_order' => 1],
            ['category_id' => 3, 'name' => 'Plain Omelette', 'slug' => 'plain-omelette', 'description' => 'Plain omelette', 'price' => 450, 'display_order' => 2],
            ['category_id' => 3, 'name' => 'Thai Seafood Omelette', 'slug' => 'thai-seafood-omelette', 'description' => 'Thai seafood omelette', 'price' => 750, 'display_order' => 3],
            ['category_id' => 3, 'name' => 'Cheese & Tomato Omelette', 'slug' => 'cheese-tomato-omelette', 'description' => 'Cheese and tomato omelette', 'price' => 750, 'display_order' => 4],
            ['category_id' => 3, 'name' => 'Wrap Omelette', 'slug' => 'wrap-omelette', 'description' => 'Wrap omelette', 'price' => 700, 'display_order' => 5],

            // === VEGETABLE CORNER ITEMS (category_id: 4) ===
            ['category_id' => 4, 'name' => 'Stir Fried Vegetables', 'slug' => 'stir-fried-vegetables', 'description' => 'Stir fried vegetables', 'price' => 1100, 'display_order' => 0],
            ['category_id' => 4, 'name' => 'Garlic Kankun', 'slug' => 'garlic-kankun', 'description' => 'Garlic fried kankun', 'price' => 950, 'display_order' => 1],
            ['category_id' => 4, 'name' => 'Vegetable Chopsuey', 'slug' => 'vegetable-chopsuey', 'description' => 'Vegetable chopsuey', 'price' => 850, 'display_order' => 2],
            ['category_id' => 4, 'name' => 'Hot Butter Mushroom', 'slug' => 'hot-butter-mushroom', 'description' => 'Hot butter mushroom', 'price' => 950, 'display_order' => 3],
            ['category_id' => 4, 'name' => 'Potato Wedges', 'slug' => 'potato-wedges', 'description' => 'Potato wedges', 'price' => 1100, 'display_order' => 4],
            ['category_id' => 4, 'name' => 'Vegetable Tempura', 'slug' => 'vegetable-tempura', 'description' => 'Vegetable tempura', 'price' => 1100, 'display_order' => 5],
            ['category_id' => 4, 'name' => 'Chilli Mushroom', 'slug' => 'chilli-mushroom', 'description' => 'Chilli mushroom', 'price' => 1100, 'display_order' => 6],
            ['category_id' => 4, 'name' => 'Vegetable Stew', 'slug' => 'vegetable-stew', 'description' => 'Vegetable stew', 'price' => 1100, 'display_order' => 7],

            // === SANDWICH ITEMS (category_id: 5) ===
            ['category_id' => 5, 'name' => 'Club Sandwich', 'slug' => 'club-sandwich', 'description' => 'Club sandwich', 'price' => 1650, 'display_order' => 0],
            ['category_id' => 5, 'name' => 'Chicken Sandwich', 'slug' => 'chicken-sandwich', 'description' => 'Chicken sandwich', 'price' => 1650, 'display_order' => 1],
            ['category_id' => 5, 'name' => 'Egg Sandwich', 'slug' => 'egg-sandwich', 'description' => 'Egg sandwich', 'price' => 1700, 'display_order' => 2],
            ['category_id' => 5, 'name' => 'Cheese & Tomato Sandwich', 'slug' => 'cheese-tomato-sandwich', 'description' => 'Cheese and tomato sandwich', 'price' => 1650, 'display_order' => 3],
            ['category_id' => 5, 'name' => 'Vegetable Sandwich', 'slug' => 'vegetable-sandwich', 'description' => 'Vegetable sandwich', 'price' => 1650, 'display_order' => 4],
        ];

        foreach ($items as $item) {
            Item::create([
                'category_id' => $item['category_id'],
                'kitchen_station_id' => 1, // Main Kitchen
                'name' => $item['name'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'price' => $item['price'],
                'is_available' => true,
                'is_featured' => false,
                'display_order' => $item['display_order'],
            ]);
        }
    }
}
