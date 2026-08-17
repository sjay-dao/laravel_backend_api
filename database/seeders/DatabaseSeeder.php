<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserRoleSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            LocationLogSeeder::class,
            AddressesSeeder::class,
            LookupSeeder::class,
            BranchSeeder::class,
            UnitSeeder::class,
            EmployeePermissionSeeder::class,
            LookupTypeSeeder::class,
        ]);
    }
}
