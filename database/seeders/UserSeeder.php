<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Explicit demo-only entry point; never insert real people or hard-coded IDs.
        $this->call(DemoAccessSeeder::class);
    }
}
