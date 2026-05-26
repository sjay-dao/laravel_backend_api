<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'user_id' => 1,
            'name' => 'Honda Click 125i',
            'sku' => 'HON-CLICK125-BLK',
            'lot_number' => 'MT-2026-001',
            'supplier_name' => 'Honda Philippines',
            'stock' => 12,
            'price' => 85000,
            'cost_price' => 78000,
            'manufactured_at' => '2026-01-15',
            'expired_at' => null,
            'category' => 'Scooter',
            'description' => 'Automatic scooter with fuel injection',
            'image' => 'products/honda-click-125i.jpg',
            'is_active' => true,
        ]);

        Product::create([
            'user_id' => 1,
            'name' => 'Yamaha NMAX',
            'sku' => 'YAM-NMAX-ABS',
            'lot_number' => 'MT-2026-002',
            'supplier_name' => 'Yamaha Motors PH',
            'stock' => 5,
            'price' => 155000,
            'cost_price' => 145000,
            'manufactured_at' => '2026-02-10',
            'expired_at' => null,
            'category' => 'Maxi Scooter',
            'description' => '155cc premium scooter with ABS',
            'image' => 'products/yamaha-nmax.jpg',
            'is_active' => true,
        ]);

        Product::create([
            'user_id' => 1,
            'name' => 'Kawasaki Barako II',
            'sku' => 'KAW-BARAKO2',
            'lot_number' => 'MT-2026-003',
            'supplier_name' => 'Kawasaki PH',
            'stock' => 8,
            'price' => 98000,
            'cost_price' => 91000,
            'manufactured_at' => '2026-03-01',
            'expired_at' => null,
            'category' => 'Business Motorcycle',
            'description' => 'Heavy duty tricycle and utility motorcycle',
            'image' => 'products/barako-2.jpg',
            'is_active' => true,
        ]);
    }
}