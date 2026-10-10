<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Sjay Pits',
                'email' => 'sjaypits@gmail.com',
                'email_verified_at' => null,
                'password' => bcrypt('Admin123!'),
                'employee_id' => null,
                'is_active' => true,
                'remember_token' => null,
                'created_at' => '2026-06-24 03:57:09',
                'updated_at' => '2026-06-24 03:57:09',
                'deleted_at' => null,
            ]
        );

        DB::table('users')->updateOrInsert(
            ['id' => 9],
            [
                'name' => 'Sjay Pits',
                'email' => 'dao.davon.au@gmail.com',
                'email_verified_at' => null,
                'password' => bcrypt('Admin123!'),
                'employee_id' => null,
                'is_active' => true,
                'remember_token' => '1Xx3dwRkYgeMEz4B5BPCurmMYTp3ILZHzDGE6psUzSbrwS6TynbgBAXyX2R8',
                'created_at' => '2026-07-03 03:44:18',
                'updated_at' => '2026-07-03 05:16:05',
                'deleted_at' => null,
            ]
        );
    }
}