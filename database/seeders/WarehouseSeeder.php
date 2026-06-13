<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Location;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Main Warehouse',
                'code' => 'WH001',
                'type' => 1,
                'address' => '100 Industrial Blvd, Warehouse District, CA 90001',
                'manager_id' => 3,
                'is_default' => 1,
                'status' => 1,
            ],
            [
                'name' => 'East Distribution Center',
                'code' => 'WH002',
                'type' => 1,
                'address' => '200 Logistics Way, New York, NY 10001',
                'manager_id' => 3,
                'is_default' => 0,
                'status' => 1,
            ],
            [
                'name' => 'West Fulfillment Hub',
                'code' => 'WH003',
                'type' => 1,
                'address' => '300 Commerce Dr, Los Angeles, CA 90001',
                'manager_id' => 3,
                'is_default' => 0,
                'status' => 1,
            ],
        ];

        foreach ($warehouses as $warehouseData) {
            $warehouse = Warehouse::create($warehouseData);
            $this->createLocations($warehouse);
        }
    }

    private function createLocations($warehouse)
    {
        $zones = ['A', 'B', 'C', 'D'];
        $shelves = range(1, 5);
        $layers = range(1, 4);
        
        $locationIndex = 1;
        foreach ($zones as $zone) {
            foreach ($shelves as $shelf) {
                foreach ($layers as $layer) {
                    Location::create([
                        'warehouse_id' => $warehouse->id,
                        'code' => "{$warehouse->code}-" . str_pad($locationIndex++, 4, '0', STR_PAD_LEFT),
                        'zone' => $zone,
                        'shelf' => $shelf,
                        'layer' => $layer,
                        'status' => 1,
                    ]);
                }
            }
        }
    }
}
