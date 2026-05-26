<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'sjaypits@gmail.com'],
            [
                'name' => 'Sjay Pits',
                'password' => bcrypt('password'),
            ]
        );
    }
}