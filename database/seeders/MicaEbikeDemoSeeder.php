<?php

namespace Database\Seeders;

use App\Domains\Inventory\Services\InventoryLotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MicaEbikeDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Mica demo data cannot be seeded in production.');
        }

        DB::transaction(function () {
            $now = now();
            DB::table('users')->updateOrInsert(['email' => 'mica.demo@example.test'], [
                'name' => 'Mica Demo Administrator', 'password' => Hash::make('demo-password'), 'is_active' => true,
                'updated_at' => $now, 'created_at' => $now,
            ]);
            $actorId = (int) DB::table('users')->where('email', 'mica.demo@example.test')->value('id');

            DB::table('branches')->updateOrInsert(['code' => 'MICA-DEMO'], [
                'name' => 'Mica E-Bike Demo Branch', 'address' => 'FICTIONAL DEMO LOCATION', 'branch_type' => 'MAIN',
                'branch_category' => 'DEMO', 'service_bay_count' => 1, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now,
            ]);
            $branchId = (int) DB::table('branches')->where('code', 'MICA-DEMO')->value('id');
            DB::table('warehouses')->updateOrInsert(['code' => 'MICA-DEMO-STOCK'], [
                'branch_id' => $branchId, 'name' => 'Mica Demo Stockroom', 'description' => 'FICTIONAL DEMO INVENTORY',
                'is_active' => true, 'updated_at' => $now, 'created_at' => $now,
            ]);
            $warehouseId = (int) DB::table('warehouses')->where('code', 'MICA-DEMO-STOCK')->value('id');

            $unitValues = ['name' => 'Piece', 'symbol' => 'pc', 'updated_at' => $now, 'created_at' => $now];
            if (Schema::hasColumn('units', 'type')) {
                $unitValues['type'] = 'count';
            }
            if (Schema::hasColumn('units', 'measurement_type')) {
                $unitValues['measurement_type'] = 'count';
            }
            if (Schema::hasColumn('units', 'is_active')) {
                $unitValues['is_active'] = true;
            }
            if (Schema::hasColumn('units', 'is_base')) {
                $unitValues['is_base'] = true;
            }
            DB::table('units')->updateOrInsert(['code' => 'pc'], $unitValues);
            $unitId = (int) DB::table('units')->where('code', 'pc')->value('id');

            foreach ([
                'DEMO-DEALER-A' => 'Demo Dealer A — Northstar Mobility',
                'DEMO-DEALER-B' => 'Demo Dealer B — Bluewheel Trading',
            ] as $code => $name) {
                DB::table('suppliers')->updateOrInsert(['code' => $code], [
                    'name' => $name, 'remarks' => 'FICTIONAL MICA DEMO DEALER', 'is_active' => true,
                    'deleted_at' => null, 'updated_at' => $now, 'created_at' => $now,
                ]);
            }
            $dealerA = (int) DB::table('suppliers')->where('code', 'DEMO-DEALER-A')->value('id');
            $dealerB = (int) DB::table('suppliers')->where('code', 'DEMO-DEALER-B')->value('id');

            $categories = ['E-BIKES' => 'E-Bikes', 'BATTERIES' => 'Batteries', 'SPARE-PARTS' => 'Spare Parts', 'ACCESSORIES' => 'Accessories'];
            foreach ($categories as $code => $name) {
                DB::table('inventory_categories')->updateOrInsert(['code' => $code], [
                    'name' => $name, 'description' => 'FICTIONAL MICA DEMO CATEGORY', 'updated_at' => $now, 'created_at' => $now,
                ]);
            }

            $products = [
                ['DEMO-EB-CITY-E1', 'CityRide E1', 'E-BIKES', 3850000],
                ['DEMO-EB-URBAN-X2', 'UrbanVolt X2', 'E-BIKES', 4500000],
                ['DEMO-EB-CARGO-C3', 'CargoMax C3', 'E-BIKES', 5800000],
                ['DEMO-BAT-48-20', '48V 20Ah Battery', 'BATTERIES', 1850000],
                ['DEMO-BAT-60-20', '60V 20Ah Battery', 'BATTERIES', 2350000],
                ['DEMO-SP-BRAKE', 'Brake Pad Set', 'SPARE-PARTS', 65000],
                ['DEMO-SP-CTRL48', 'Controller 48V', 'SPARE-PARTS', 280000],
                ['DEMO-SP-THROTTLE', 'Throttle Assembly', 'SPARE-PARTS', 95000],
                ['DEMO-ACC-BASKET', 'Rear Basket', 'ACCESSORIES', 120000],
                ['DEMO-ACC-PHONE', 'Phone Holder', 'ACCESSORIES', 45000],
                ['DEMO-ACC-RAIN', 'Rain Cover', 'ACCESSORIES', 75000],
            ];
            $productIds = [];
            foreach ($products as [$code, $name, $categoryCode, $retailPrice]) {
                $categoryId = (int) DB::table('inventory_categories')->where('code', $categoryCode)->value('id');
                DB::table('inventory_objects')->updateOrInsert(['code' => $code], [
                    'name' => $name, 'brand' => 'Mica Demo', 'variant' => 'FICTIONAL DEMO',
                    'inventory_category_id' => $categoryId, 'unit_id' => $unitId, 'track_inventory' => true,
                    'is_sellable' => true, 'is_active' => true, 'retail_price_cents' => $retailPrice,
                    'updated_at' => $now, 'created_at' => $now,
                ]);
                $productId = (int) DB::table('inventory_objects')->where('code', $code)->value('id');
                $productIds[$code] = $productId;
                DB::table('inventory_object_units')->updateOrInsert(
                    ['inventory_object_id' => $productId, 'unit_id' => $unitId],
                    ['conversion_factor' => 1, 'updated_at' => $now, 'created_at' => $now]
                );
            }

            DB::table('inventory_wholesale_price_tiers')->where('inventory_object_id', $productIds['DEMO-EB-URBAN-X2'])->delete();
            foreach ([[3, 5, 4250000], [6, 10, 4050000], [11, null, 3900000]] as [$min, $max, $price]) {
                DB::table('inventory_wholesale_price_tiers')->insert([
                    'inventory_object_id' => $productIds['DEMO-EB-URBAN-X2'], 'min_quantity' => $min,
                    'max_quantity' => $max, 'unit_price_cents' => $price, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $lots = [
                ['DEMO-EB-CITY-E1', 'OWNED', null, 8, 3100000],
                ['DEMO-EB-URBAN-X2', 'OWNED', null, 5, 3600000],
                ['DEMO-EB-URBAN-X2', 'CONSIGNMENT', $dealerA, 4, 3500000],
                ['DEMO-EB-URBAN-X2', 'CONSIGNMENT', $dealerB, 3, 3550000],
                ['DEMO-EB-CARGO-C3', 'CONSIGNMENT', $dealerA, 4, 4700000],
                ['DEMO-BAT-48-20', 'OWNED', null, 10, 1400000],
                ['DEMO-BAT-60-20', 'OWNED', null, 6, 1800000],
                ['DEMO-BAT-60-20', 'CONSIGNMENT', $dealerB, 4, 1750000],
                ['DEMO-SP-BRAKE', 'OWNED', null, 30, 35000],
                ['DEMO-SP-CTRL48', 'CONSIGNMENT', $dealerA, 12, 190000],
                ['DEMO-SP-THROTTLE', 'OWNED', null, 20, 55000],
                ['DEMO-ACC-BASKET', 'OWNED', null, 15, 75000],
                ['DEMO-ACC-PHONE', 'CONSIGNMENT', $dealerB, 25, 25000],
                ['DEMO-ACC-RAIN', 'OWNED', null, 12, 42000],
            ];
            foreach ($lots as $index => [$code, $ownership, $supplierId, $quantity, $cost]) {
                $note = 'MICA-DEMO-LOT-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                if (! DB::table('inventory_lots')->where('notes', $note)->exists()) {
                    app(InventoryLotService::class)->receive([
                        'inventory_object_id' => $productIds[$code], 'warehouse_id' => $warehouseId,
                        'supplier_id' => $supplierId, 'ownership' => $ownership, 'quantity_received' => $quantity,
                        'settlement_cost_cents' => $cost, 'received_date' => now()->toDateString(), 'notes' => $note,
                    ], $actorId);
                }
            }
        }, 3);
    }
}
