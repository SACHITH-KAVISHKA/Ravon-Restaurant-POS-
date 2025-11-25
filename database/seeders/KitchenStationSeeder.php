<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KitchenStation;

class KitchenStationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KitchenStation::create([
            'name' => 'Main Kitchen',
            'printer_name' => 'Kitchen-Epson-TM-T88',
            'printer_ip' => '192.168.1.101',
            'is_active' => true,
        ]);

        KitchenStation::create([
            'name' => 'Bar',
            'printer_name' => 'Bar-Epson-TM-T88',
            'printer_ip' => '192.168.1.102',
            'is_active' => true,
        ]);

        KitchenStation::create([
            'name' => 'Grill Station',
            'printer_name' => 'Grill-Epson-TM-T88',
            'printer_ip' => '192.168.1.103',
            'is_active' => true,
        ]);

        KitchenStation::create([
            'name' => 'Pastry',
            'printer_name' => 'Pastry-Epson-TM-T88',
            'printer_ip' => '192.168.1.104',
            'is_active' => true,
        ]);
    }
}
