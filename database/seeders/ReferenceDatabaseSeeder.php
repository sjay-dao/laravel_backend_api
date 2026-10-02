<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReferenceDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LookupTypeSeeder::class, LookupSeeder::class, UnitSeeder::class,
            SalesOrderStatusSeeder::class, PermissionSeeder::class, EvidencePermissionSeeder::class,
            RoleSeeder::class, RolePermissionSeeder::class, EmployeePermissionSeeder::class,
        ]);
    }
}
