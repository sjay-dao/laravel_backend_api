<?php

namespace Database\Seeders;

use App\Support\DemoDatabaseGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoAccessSeeder extends Seeder
{
    public const CASHIER_PERMISSIONS = [
        'sales.sales.view', 'sales.sales.create', 'sales.sales.update',
        'inventory.products.view', 'inventory.lots.view', 'reference.branches.view',
        'reference.customers.view', 'reference.customers.create', 'reference.suppliers.view',
    ];

    public function run(): void
    {
        app(DemoDatabaseGuard::class)->assertSafe();
        DB::table('roles')->updateOrInsert(['code' => 'cashier'], ['name' => 'Cashier', 'description' => 'Demo POS operator', 'is_active' => true]);
        foreach (['admin' => 'Administrator', 'manager' => 'Manager', 'cashier' => 'Cashier'] as $code => $name) {
            $roleId = DB::table('roles')->where('code', $code)->value('id');
            $permissions = DB::table('permissions')->where('is_active', true);
            if ($code === 'cashier') {
                $permissions->whereIn('code', self::CASHIER_PERMISSIONS);
            } elseif ($code === 'manager') {
                $permissions->whereIn('module', ['inventory', 'sales', 'finance', 'reference', 'dashboard']);
            }
            foreach ($permissions->pluck('id') as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], ['created_at' => now(), 'updated_at' => now()]);
            }
            $email = "$code@mica.demo";
            if (! DB::table('users')->where('email', $email)->exists()) {
                DB::table('users')->insert(['email' => $email, 'name' => "Mica Demo $name", 'password' => Hash::make(config('demo.password')), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('user_roles')->updateOrInsert(['user_id' => DB::table('users')->where('email', $email)->value('id'), 'role_id' => $roleId], ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
