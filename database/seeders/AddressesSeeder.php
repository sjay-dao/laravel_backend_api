<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AddressesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('addresses')->insert([
            [
                'user_id' => 1,
                'label' => 'Home',
                'contact_person' => 'John Doe',
                'contact_number' => '09171234567',
                'address_line_1' => '123 Sample Street',
                'address_line_2' => 'Unit 101',
                'barangay' => 'Barangay 123',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'postal_code' => '1001',
                'latitude' => 14.599512,
                'longitude' => 120.984222,
                'is_default' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 1,
                'label' => 'Office',
                'contact_person' => 'John Doe',
                'contact_number' => '09171234567',
                'address_line_1' => '456 Business Ave',
                'address_line_2' => '5th Floor',
                'barangay' => 'Barangay 456',
                'city' => 'Makati',
                'province' => 'Metro Manila',
                'postal_code' => '1226',
                'latitude' => 14.554729,
                'longitude' => 121.024445,
                'is_default' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 2,
                'label' => 'Branch',
                'contact_person' => 'Branch Manager',
                'contact_number' => '09179876543',
                'address_line_1' => '789 Branch Road',
                'address_line_2' => null,
                'barangay' => 'Barangay 789',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'postal_code' => '1100',
                'latitude' => 14.676041,
                'longitude' => 121.043700,
                'is_default' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}