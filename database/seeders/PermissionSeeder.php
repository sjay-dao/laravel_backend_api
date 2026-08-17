<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // ===========================
            // SYSTEM
            // ===========================
            ['module' => 'system', 'resource' => 'users', 'action' => 'view'],
            ['module' => 'system', 'resource' => 'users', 'action' => 'create'],
            ['module' => 'system', 'resource' => 'users', 'action' => 'update'],
            ['module' => 'system', 'resource' => 'users', 'action' => 'delete'],

            ['module' => 'system', 'resource' => 'roles', 'action' => 'view'],
            ['module' => 'system', 'resource' => 'roles', 'action' => 'create'],
            ['module' => 'system', 'resource' => 'roles', 'action' => 'update'],
            ['module' => 'system', 'resource' => 'roles', 'action' => 'delete'],

            ['module' => 'system', 'resource' => 'permissions', 'action' => 'view'],
            ['module' => 'system', 'resource' => 'permissions', 'action' => 'assign'],

            // ===========================
            // EMPLOYEE
            // ===========================
            ['module' => 'employee', 'resource' => 'employees', 'action' => 'view'],
            ['module' => 'employee', 'resource' => 'employees', 'action' => 'create'],
            ['module' => 'employee', 'resource' => 'employees', 'action' => 'update'],
            ['module' => 'employee', 'resource' => 'employees', 'action' => 'delete'],

            // ===========================
            // INVENTORY
            // ===========================
            ['module' => 'inventory', 'resource' => 'products', 'action' => 'view'],
            ['module' => 'inventory', 'resource' => 'products', 'action' => 'create'],
            ['module' => 'inventory', 'resource' => 'products', 'action' => 'update'],
            ['module' => 'inventory', 'resource' => 'products', 'action' => 'delete'],

            ['module' => 'inventory', 'resource' => 'stocks', 'action' => 'view'],
            ['module' => 'inventory', 'resource' => 'stocks', 'action' => 'adjust'],

            // ===========================
            // PAYROLL
            // ===========================
            ['module' => 'payroll', 'resource' => 'payroll', 'action' => 'view'],
            ['module' => 'payroll', 'resource' => 'payroll', 'action' => 'process'],

            // ===========================
            // REFERENCES
            // ===========================
            ['module' => 'reference', 'resource' => 'lookups', 'action' => 'view'],
            ['module' => 'reference', 'resource' => 'lookups', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {

            DB::table('permissions')->updateOrInsert(
                [
                    'module' => $permission['module'],
                    'resource' => $permission['resource'],
                    'action' => $permission['action'],
                ],
                [
                    'code' => "{$permission['module']}.{$permission['resource']}.{$permission['action']}",
                    'description' => ucfirst($permission['action']) . ' ' . ucfirst($permission['resource']),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}