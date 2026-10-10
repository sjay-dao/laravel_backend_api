<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Address;
use Carbon\Carbon;

class AddressesSeeder extends Seeder
{
     public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {

            Address::create([
                'user_id' => null,

                'label' => fake()->randomElement([
                    'Home',
                    'Office',
                    'Warehouse',
                    'Billing',
                    'Shipping',
                ]),

                'contact_person' => fake()->name(),
                'contact_number' => fake()->phoneNumber(),

                'address_line_1' => fake()->streetAddress(),
                'address_line_2' => fake()->secondaryAddress(),

                // Make sure these IDs exist in your PSGC table
                'barangay_id' => rand(1, 1000),

                'postal_code' => fake()->postcode(),

                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),

                'is_default' => $i === 1,
            ]);
        }
    }
}