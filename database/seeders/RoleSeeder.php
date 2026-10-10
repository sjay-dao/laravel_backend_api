<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'code' => 'admin',
                'name' => 'Administrator',
                'description' => 'Full system access',
            ],
            [
                'code' => 'customer',
                'name' => 'Customer',
                'description' => 'Places orders',
            ],
            [
                'code' => 'branch_staff',
                'name' => 'Branch Staff',
                'description' => 'Processes orders and inventory',
            ],
            [
                'code' => 'delivery_rider',
                'name' => 'Delivery Rider',
                'description' => 'Delivers orders',
            ],
            [
                'code' => 'supplier',
                'name' => 'Supplier',
                'description' => 'Provides products to branches',
            ],
            [
                'code' => 'manager',
                'name' => 'Manager',
                'description' => 'Oversees branch operations',
            ],
        ]);
    }

}
