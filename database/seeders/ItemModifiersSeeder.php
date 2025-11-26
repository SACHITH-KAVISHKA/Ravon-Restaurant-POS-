<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemModifier;

class ItemModifiersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Find or create categories
            $foodCategory = Category::firstOrCreate(
                ['name' => 'Rice & Noodles'],
                ['description' => 'Rice and Noodle dishes', 'is_active' => true]
            );

            $beverageCategory = Category::firstOrCreate(
                ['name' => 'Beverages'],
                ['description' => 'Drinks and Beverages', 'is_active' => true]
            );

            // Create Rice items with portion sizes
            $friedRice = Item::firstOrCreate(
                ['name' => 'Fried Rice', 'category_id' => $foodCategory->id],
                [
                    'slug' => 'fried-rice',
                    'description' => 'Classic fried rice',
                    'price' => 250.00,
                    'is_available' => true,
                ]
            );

            // Add portion modifiers for Fried Rice
            ItemModifier::firstOrCreate(
                ['item_id' => $friedRice->id, 'name' => 'Small'],
                [
                    'type' => 'portion',
                    'price_adjustment' => -50.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $friedRice->id, 'name' => 'Large'],
                [
                    'type' => 'portion',
                    'price_adjustment' => 100.00,
                    'is_active' => true,
                ]
            );

            // Create Nasi Goreng with portions
            $nasiGoreng = Item::firstOrCreate(
                ['name' => 'Nasi Goreng', 'category_id' => $foodCategory->id],
                [
                    'slug' => 'nasi-goreng',
                    'description' => 'Indonesian fried rice',
                    'price' => 300.00,
                    'is_available' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $nasiGoreng->id, 'name' => 'Small'],
                [
                    'type' => 'portion',
                    'price_adjustment' => -80.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $nasiGoreng->id, 'name' => 'Regular'],
                [
                    'type' => 'portion',
                    'price_adjustment' => 0.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $nasiGoreng->id, 'name' => 'Large'],
                [
                    'type' => 'portion',
                    'price_adjustment' => 120.00,
                    'is_active' => true,
                ]
            );

            // Create Coca Cola with size options
            $cocaCola = Item::firstOrCreate(
                ['name' => 'Coca Cola', 'category_id' => $beverageCategory->id],
                [
                    'slug' => 'coca-cola',
                    'description' => 'Refreshing cola drink',
                    'price' => 100.00,
                    'is_available' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $cocaCola->id, 'name' => '250ml'],
                [
                    'type' => 'size',
                    'price_adjustment' => 0.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $cocaCola->id, 'name' => '500ml'],
                [
                    'type' => 'size',
                    'price_adjustment' => 50.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $cocaCola->id, 'name' => '1 Liter'],
                [
                    'type' => 'size',
                    'price_adjustment' => 120.00,
                    'is_active' => true,
                ]
            );

            // Create Orange Juice with sizes
            $orangeJuice = Item::firstOrCreate(
                ['name' => 'Fresh Orange Juice', 'category_id' => $beverageCategory->id],
                [
                    'slug' => 'fresh-orange-juice',
                    'description' => 'Freshly squeezed orange juice',
                    'price' => 150.00,
                    'is_available' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $orangeJuice->id, 'name' => '200ml'],
                [
                    'type' => 'size',
                    'price_adjustment' => -30.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $orangeJuice->id, 'name' => '350ml'],
                [
                    'type' => 'size',
                    'price_adjustment' => 0.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $orangeJuice->id, 'name' => '500ml'],
                [
                    'type' => 'size',
                    'price_adjustment' => 80.00,
                    'is_active' => true,
                ]
            );

            // Create Mineral Water with sizes
            $water = Item::firstOrCreate(
                ['name' => 'Mineral Water', 'category_id' => $beverageCategory->id],
                [
                    'slug' => 'mineral-water',
                    'description' => 'Pure mineral water',
                    'price' => 50.00,
                    'is_available' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $water->id, 'name' => '330ml'],
                [
                    'type' => 'size',
                    'price_adjustment' => 0.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $water->id, 'name' => '500ml'],
                [
                    'type' => 'size',
                    'price_adjustment' => 20.00,
                    'is_active' => true,
                ]
            );

            ItemModifier::firstOrCreate(
                ['item_id' => $water->id, 'name' => '1.5 Liter'],
                [
                    'type' => 'size',
                    'price_adjustment' => 50.00,
                    'is_active' => true,
                ]
            );

            $this->command->info('Item modifiers seeded successfully!');
            $this->command->info('Created items with portions and sizes:');
            $this->command->info('- Fried Rice (Small, Large)');
            $this->command->info('- Nasi Goreng (Small, Regular, Large)');
            $this->command->info('- Coca Cola (250ml, 500ml, 1 Liter)');
            $this->command->info('- Fresh Orange Juice (200ml, 350ml, 500ml)');
            $this->command->info('- Mineral Water (330ml, 500ml, 1.5 Liter)');
        } catch (\Exception $e) {
            $this->command->error('Error seeding item modifiers: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            throw $e;
        }
    }
}
