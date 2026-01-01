<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            KitchenStationSeeder::class,
            TableSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
            MainStockItemSeeder::class,
        ]);
    }
}
