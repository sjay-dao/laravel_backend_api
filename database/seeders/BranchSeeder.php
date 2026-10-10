<?php

namespace Database\Seeders;

use App\Domains\Reference\Models\Barangay;
use App\Domains\Reference\Models\Branch;
use Illuminate\Database\Seeder;
use App\Domains\System\Models\PsgcBarangay;
class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['code' => 'MNL001', 'name' => 'Manila Main Branch'],
            ['code' => 'QC001', 'name' => 'Quezon City Branch'],
            ['code' => 'MKT001', 'name' => 'Makati Branch'],
            ['code' => 'PSG001', 'name' => 'Pasig Branch'],
            ['code' => 'CLN001', 'name' => 'Caloocan Branch'],
        ];

        $barangayIds = PsgcBarangay::query()
            ->inRandomOrder()
            ->limit(count($branches))
            ->pluck('id')
            ->values();

        foreach ($branches as $index => $branch) {
            Branch::updateOrCreate(
                [
                    'code' => $branch['code'],
                ],
                [
                    'name' => $branch['name'],
                    'address' => fake()->streetAddress(),
                    'barangay_id' => $barangayIds[$index] ?? null,
                    'branch_type' => fake()->randomElement([
                        'MAIN',
                        'SATELLITE',
                    ]),
                    'branch_category' => fake()->randomElement([
                        'A',
                        'B',
                        'C',
                    ]),
                    'service_bay_count' => fake()->numberBetween(0, 10),
                    'is_active' => true,
                ]
            );
        }
    }
}