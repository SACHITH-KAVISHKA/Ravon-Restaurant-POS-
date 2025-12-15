<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;

class FloorAndTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create tables (48 tables total)
        for ($i = 1; $i <= 48; $i++) {
            Table::create([
                'table_number' => sprintf('T-%02d', $i),
                'capacity' => rand(2, 8),
                'status' => 'available',
                'is_active' => true,
            ]);
        }
    }
}
