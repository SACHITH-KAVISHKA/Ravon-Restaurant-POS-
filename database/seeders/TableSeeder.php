<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Table;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing tables
        Table::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Create tables T1 to T30
        for ($i = 1; $i <= 30; $i++) {
            Table::create([
                'table_number' => 'T' . $i,
                'capacity' => 4,
                'status' => 'available',
                'is_active' => true,
            ]);
        }
    }
}
