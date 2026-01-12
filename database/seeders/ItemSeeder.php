<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing data
        DB::table('item_modifiers')->truncate();
        DB::table('items')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Insert items - Run from SQL file or use artisan command:
        // php artisan db:seed --class=ItemSeeder
        // Then manually import the items SQL

        $this->command->info('ItemSeeder: Tables cleared. Please import items and item_modifiers data manually via phpMyAdmin or run:');
        $this->command->info('mysql -u root -p your_database < items_data.sql');
    }
}
