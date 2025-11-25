<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Floor;
use App\Models\Table;

class FloorAndTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create floors
        $restaurant = Floor::create([
            'name' => 'RESTAURANT',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $garden = Floor::create([
            'name' => 'GARDEN',
            'display_order' => 2,
            'is_active' => true,
        ]);

        $acRoom = Floor::create([
            'name' => 'AC ROOM',
            'display_order' => 3,
            'is_active' => true,
        ]);

        // Create tables for Restaurant floor (30 tables in 6x5 grid)
        for ($i = 1; $i <= 30; $i++) {
            $row = ceil($i / 6);
            $col = (($i - 1) % 6) + 1;
            
            Table::create([
                'floor_id' => $restaurant->id,
                'table_number' => sprintf('T-%02d', $i),
                'capacity' => rand(2, 6),
                'status' => 'available',
                'position_x' => $col,
                'position_y' => $row,
                'is_active' => true,
            ]);
        }

        // Create tables for Garden floor (10 tables)
        for ($i = 1; $i <= 10; $i++) {
            Table::create([
                'floor_id' => $garden->id,
                'table_number' => sprintf('G-%02d', $i),
                'capacity' => rand(4, 8),
                'status' => 'available',
                'position_x' => (($i - 1) % 5) + 1,
                'position_y' => ceil($i / 5),
                'is_active' => true,
            ]);
        }

        // Create tables for AC Room (8 tables)
        for ($i = 1; $i <= 8; $i++) {
            Table::create([
                'floor_id' => $acRoom->id,
                'table_number' => sprintf('AC-%02d', $i),
                'capacity' => rand(2, 4),
                'status' => 'available',
                'position_x' => (($i - 1) % 4) + 1,
                'position_y' => ceil($i / 4),
                'is_active' => true,
            ]);
        }
    }
}
