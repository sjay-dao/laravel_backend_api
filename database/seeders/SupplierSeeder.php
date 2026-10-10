<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Reference\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP001',
                'name' => 'Motorcycle Parts Trading Inc.',
                'contact_person' => 'Juan Dela Cruz',
                'contact_number' => '09171234567',
                'email' => 'sales@motorparts.test',
                'address' => '123 Main Street',
                'barangay_id' => 6006,
                'tin' => '123-456-789-000',
                'remarks' => 'Primary motorcycle parts supplier.',
                'is_active' => true,
            ],
            [
                'code' => 'SUP002',
                'name' => 'Philippine Lubricants Corporation',
                'contact_person' => 'Maria Santos',
                'contact_number' => '09181234567',
                'email' => 'sales@philippinelubricants.test',
                'address' => '456 Industrial Avenue',
                'barangay_id' => 6006,
                'tin' => '234-567-890-000',
                'remarks' => 'Lubricants and maintenance products.',
                'is_active' => true,
            ],
            [
                'code' => 'SUP003',
                'name' => 'ABC Industrial Supply',
                'contact_person' => 'Pedro Reyes',
                'contact_number' => '09191234567',
                'email' => 'info@abcindustrial.test',
                'address' => '789 Commerce Road',
                'barangay_id' => 6006,
                'tin' => '345-678-901-000',
                'remarks' => null,
                'is_active' => true,
            ],
        ];
        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['code' => $supplier['code']],
                $supplier
            );
        }
    }
}