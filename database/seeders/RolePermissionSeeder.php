<?php

namespace Database\Seeders;

use App\Domains\System\Models\Permission;
use App\Domains\System\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('role_permissions')->truncate();

        $admin = Role::where('code', 'admin')->first();

        if ($admin) {

            foreach (Permission::pluck('id') as $permissionId) {

                DB::table('role_permissions')->insert([
                    'role_id' => $admin->id,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}