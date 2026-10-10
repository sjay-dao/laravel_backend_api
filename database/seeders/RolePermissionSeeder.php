<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = DB::table('roles')->where('code', 'admin')->value('id');
        if ($admin) {
            foreach (DB::table('permissions')->pluck('id') as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $admin, 'permission_id' => $permissionId], ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
