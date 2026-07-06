<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Silicon Valley Logistics Center',
                'latitude' => 37.40381900,
                'longitude' => -122.03126700,
            ],
            [
                'name' => 'New York East Coast Hub',
                'latitude' => 40.71277600,
                'longitude' => -74.00597400,
            ],
            [
                'name' => 'Midwest Distribution Depot (Chicago)',
                'latitude' => 41.87811300,
                'longitude' => -87.62979900,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(['name' => $warehouse['name']], $warehouse);
        }
    }
}
