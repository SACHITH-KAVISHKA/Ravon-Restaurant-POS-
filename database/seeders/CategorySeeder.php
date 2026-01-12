<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing categories
        Category::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Insert new categories with specific IDs
        $categories = [
            ['id' => 1, 'name' => 'Soup', 'slug' => 'soup', 'description' => 'Soups', 'display_order' => 0],
            ['id' => 2, 'name' => 'Salad', 'slug' => 'salad', 'description' => 'Fresh salads', 'display_order' => 1],
            ['id' => 3, 'name' => 'Omelette', 'slug' => 'omelette', 'description' => 'Omelettes', 'display_order' => 2],
            ['id' => 4, 'name' => 'Vegetable Corner', 'slug' => 'vegetable-corner', 'description' => 'Vegetarian dishes', 'display_order' => 3],
            ['id' => 5, 'name' => 'Sandwich', 'slug' => 'sandwich', 'description' => 'Sandwiches', 'display_order' => 4],
            ['id' => 6, 'name' => 'Chicken', 'slug' => 'chicken', 'description' => 'Chicken dishes', 'display_order' => 5],
            ['id' => 7, 'name' => 'Cuttlefish', 'slug' => 'cuttlefish', 'description' => 'Cuttlefish dishes', 'display_order' => 6],
            ['id' => 8, 'name' => 'Fish', 'slug' => 'fish', 'description' => 'Fish dishes', 'display_order' => 7],
            ['id' => 9, 'name' => 'Prawns', 'slug' => 'prawns', 'description' => 'Prawn dishes', 'display_order' => 8],
            ['id' => 10, 'name' => 'Pork', 'slug' => 'pork', 'description' => 'Pork dishes', 'display_order' => 9],
            ['id' => 11, 'name' => 'Mutton', 'slug' => 'mutton', 'description' => 'Mutton dishes', 'display_order' => 10],
            ['id' => 12, 'name' => 'Crab', 'slug' => 'crab', 'description' => 'Crab dishes', 'display_order' => 11],
            ['id' => 13, 'name' => 'Rice Specials', 'slug' => 'rice', 'description' => 'Rice based dishes', 'display_order' => 12],
            ['id' => 14, 'name' => 'Noodles Specials', 'slug' => 'noodles', 'description' => 'Noodle based dishes', 'display_order' => 13],
            ['id' => 15, 'name' => 'On the Grill', 'slug' => 'grill', 'description' => 'Grilled items', 'display_order' => 14],
            ['id' => 16, 'name' => 'International', 'slug' => 'international', 'description' => 'International cuisine', 'display_order' => 15],
            ['id' => 17, 'name' => 'Specials', 'slug' => 'specials', 'description' => 'Special dishes', 'display_order' => 16],
            ['id' => 18, 'name' => 'Dip & Bite', 'slug' => 'dip-bite', 'description' => 'Starters and sides', 'display_order' => 17],
            ['id' => 19, 'name' => 'Family Packs', 'slug' => 'family-packs', 'description' => 'Family meals', 'display_order' => 18],
            ['id' => 20, 'name' => 'Dessert', 'slug' => 'desserts', 'description' => 'Sweet treats', 'display_order' => 19],
            ['id' => 21, 'name' => 'Beverages', 'slug' => 'beverages', 'description' => null, 'display_order' => 20],
            ['id' => 22, 'name' => 'Others', 'slug' => 'others', 'description' => null, 'display_order' => 21],
        ];

        foreach ($categories as $category) {
            Category::create([
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'image' => null,
                'display_order' => $category['display_order'],
                'is_active' => true,
            ]);
        }
    }
}
