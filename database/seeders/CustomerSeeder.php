<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Domains\Reference\Models\Customer;
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'code' => 'CUS001',
                'name' => 'Juan Dela Cruz',
                'contact_person' => 'Juan Dela Cruz',
                'contact_number' => '09171234567',
                'email' => 'juan.delacruz@test.com',
                'address' => '123 Main Street',
                'barangay_id' => 6006,
                'tin' => '123-456-789-000',
                'remarks' => 'Regular customer.',
                'is_active' => true,
            ],
            [
                'code' => 'CUS002',
                'name' => 'Maria Santos',
                'contact_person' => 'Maria Santos',
                'contact_number' => '09181234567',
                'email' => 'maria.santos@test.com',
                'address' => '456 Industrial Avenue',
                'barangay_id' => 6006,
                'tin' => '234-567-890-000',
                'remarks' => 'Wholesale customer.',
                'is_active' => true,
            ],
            [
                'code' => 'CUS003',
                'name' => 'Pedro Reyes',
                'contact_person' => 'Pedro Reyes',
                'contact_number' => '09191234567',
                'email' => 'pedro.reyes@test.com',
                'address' => '789 Commerce Road',
                'barangay_id' => 6006,
                'tin' => '345-678-901-000',
                'remarks' => null,
                'is_active' => true,
            ],
            [
                'code' => 'CUS004',
                'name' => 'ABC Motors and Parts',
                'contact_person' => 'Carlos Mendoza',
                'contact_number' => '09201234567',
                'email' => 'sales@abcmotors.test',
                'address' => '101 Highway Road',
                'barangay_id' => 6006,
                'tin' => '456-789-012-000',
                'remarks' => 'Corporate customer.',
                'is_active' => true,
            ],
            [
                'code' => 'CUS005',
                'name' => 'Rider Supply Center',
                'contact_person' => 'Mark Garcia',
                'contact_number' => '09211234567',
                'email' => 'ridersupply@test.com',
                'address' => '202 Market Street',
                'barangay_id' => 6006,
                'tin' => null,
                'remarks' => 'Retail and wholesale customer.',
                'is_active' => true,
            ],
        ];
        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['code' => $customer['code']],
                $customer
            );
        }
    }
}