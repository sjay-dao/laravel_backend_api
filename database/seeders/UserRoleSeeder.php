<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Demo identities are resolved by stable email/code, never hard-coded IDs.
        $this->call(DemoAccessSeeder::class);
    }
}
