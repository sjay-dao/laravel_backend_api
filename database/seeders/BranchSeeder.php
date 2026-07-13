<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Address;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'code' => 'MNL001',
                'name' => 'Manila Main Branch',
            ],
            [
                'code' => 'QC001',
                'name' => 'Quezon City Branch',
            ],
            [
                'code' => 'MKT001',
                'name' => 'Makati Branch',
            ],
            [
                'code' => 'PSG001',
                'name' => 'Pasig Branch',
            ],
            [
                'code' => 'CLN001',
                'name' => 'Caloocan Branch',
            ],
        ];

        foreach ($branches as $item) {

            $address = Address::create([
                'label' => $item['name'],
                'address_line_1' => fake()->streetAddress(),
                'address_line_2' => fake()->secondaryAddress(),
                'barangay_id' => rand(1, 1000), // adjust to your PSGC data
                'postal_code' => fake()->postcode(),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'is_default' => true,
            ]);

            Branch::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'address_id' => $address->id,
                'is_active' => true,
            ]);
        }
    }
}
