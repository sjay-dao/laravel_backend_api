<?php

namespace Database\Seeders;

use App\Domains\System\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('user_roles')->truncate();

        $admin = Role::where('code', 'admin')->first();

        if (!$admin) {
            return;
        }

        foreach ([1, 9] as $userId) {

            DB::table('user_roles')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'role_id' => $admin->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}