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

        // Insert new categories
        $categories = [
            ['name' => 'Soup', 'slug' => 'soup', 'description' => 'Soups', 'display_order' => 0],
            ['name' => 'Salad', 'slug' => 'salad', 'description' => 'Fresh salads', 'display_order' => 1],
            ['name' => 'Omelette', 'slug' => 'omelette', 'description' => 'Omelettes', 'display_order' => 2],
            ['name' => 'Vegetable Corner', 'slug' => 'vegetable-corner', 'description' => 'Vegetarian dishes', 'display_order' => 3],
            ['name' => 'Sandwich', 'slug' => 'sandwich', 'description' => 'Sandwiches', 'display_order' => 4],
            ['name' => 'Chicken', 'slug' => 'chicken', 'description' => 'Chicken dishes', 'display_order' => 5],
            ['name' => 'Cuttlefish', 'slug' => 'cuttlefish', 'description' => 'Cuttlefish dishes', 'display_order' => 6],
            ['name' => 'Fish', 'slug' => 'fish', 'description' => 'Fish dishes', 'display_order' => 7],
            ['name' => 'Prawns', 'slug' => 'prawns', 'description' => 'Prawn dishes', 'display_order' => 8],
            ['name' => 'Pork', 'slug' => 'pork', 'description' => 'Pork dishes', 'display_order' => 9],
            ['name' => 'Mutton', 'slug' => 'mutton', 'description' => 'Mutton dishes', 'display_order' => 10],
            ['name' => 'Crab', 'slug' => 'crab', 'description' => 'Crab dishes', 'display_order' => 11],
            ['name' => 'Rice Specials', 'slug' => 'rice', 'description' => 'Rice based dishes', 'display_order' => 12],
            ['name' => 'Noodles Specials', 'slug' => 'noodles', 'description' => 'Noodle based dishes', 'display_order' => 13],
            ['name' => 'On the Grill', 'slug' => 'grill', 'description' => 'Grilled items', 'display_order' => 14],
            ['name' => 'International', 'slug' => 'international', 'description' => 'International cuisine', 'display_order' => 15],
            ['name' => 'Specials', 'slug' => 'specials', 'description' => 'Special dishes', 'display_order' => 16],
            ['name' => 'Dip & Bite', 'slug' => 'dip-bite', 'description' => 'Starters and sides', 'display_order' => 17],
            ['name' => 'Family Packs', 'slug' => 'family-packs', 'description' => 'Family meals', 'display_order' => 18],
            ['name' => 'Dessert', 'slug' => 'desserts', 'description' => 'Sweet treats', 'display_order' => 19],
        ];

        foreach ($categories as $category) {
            Category::create([
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
