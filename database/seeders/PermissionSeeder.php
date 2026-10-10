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
            ['module' => 'inventory', 'resource' => 'lots', 'action' => 'view'],
            ['module' => 'inventory', 'resource' => 'lots', 'action' => 'create'],

            // ===========================
            // FINANCE
            // ===========================
            ['module' => 'finance', 'resource' => 'expenses', 'action' => 'view'],
            ['module' => 'finance', 'resource' => 'expenses', 'action' => 'create'],

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

            // Current backend and frontend authorization contracts.
            ['module' => 'dashboard', 'resource' => 'dashboards', 'action' => 'view'],
            ['module' => 'employee', 'resource' => 'attendance', 'action' => 'create'],
            ['module' => 'employee', 'resource' => 'attendance', 'action' => 'delete'],
            ['module' => 'employee', 'resource' => 'attendance', 'action' => 'update'],
            ['module' => 'employee', 'resource' => 'attendance', 'action' => 'view'],
            ['module' => 'employee', 'resource' => 'employees', 'action' => 'edit'],
            ['module' => 'employee', 'resource' => 'reports', 'action' => 'view'],
            ['module' => 'employee', 'resource' => 'schedule', 'action' => 'assign'],
            ['module' => 'employee', 'resource' => 'schedule', 'action' => 'create'],
            ['module' => 'employee', 'resource' => 'schedule', 'action' => 'delete'],
            ['module' => 'employee', 'resource' => 'schedule', 'action' => 'update'],
            ['module' => 'employee', 'resource' => 'schedule', 'action' => 'view'],
            ['module' => 'employee', 'resource' => 'schedules', 'action' => 'create'],
            ['module' => 'employee', 'resource' => 'schedules', 'action' => 'delete'],
            ['module' => 'employee', 'resource' => 'schedules', 'action' => 'update'],
            ['module' => 'employee', 'resource' => 'schedules', 'action' => 'view'],
            ['module' => 'employee', 'resource' => 'transactions', 'action' => 'create'],
            ['module' => 'employee', 'resource' => 'transactions', 'action' => 'update'],
            ['module' => 'employee', 'resource' => 'transactions', 'action' => 'view'],
            ['module' => 'inventory', 'resource' => 'categories', 'action' => 'create'],
            ['module' => 'inventory', 'resource' => 'categories', 'action' => 'delete'],
            ['module' => 'inventory', 'resource' => 'categories', 'action' => 'update'],
            ['module' => 'inventory', 'resource' => 'categories', 'action' => 'view'],
            ['module' => 'inventory', 'resource' => 'movements', 'action' => 'create'],
            ['module' => 'inventory', 'resource' => 'movements', 'action' => 'delete'],
            ['module' => 'inventory', 'resource' => 'movements', 'action' => 'update'],
            ['module' => 'inventory', 'resource' => 'movements', 'action' => 'view'],
            ['module' => 'purchasing', 'resource' => 'purchasing', 'action' => 'view'],
            ['module' => 'reference', 'resource' => 'branches', 'action' => 'create'],
            ['module' => 'reference', 'resource' => 'branches', 'action' => 'delete'],
            ['module' => 'reference', 'resource' => 'branches', 'action' => 'update'],
            ['module' => 'reference', 'resource' => 'branches', 'action' => 'view'],
            ['module' => 'reference', 'resource' => 'customers', 'action' => 'create'],
            ['module' => 'reference', 'resource' => 'customers', 'action' => 'delete'],
            ['module' => 'reference', 'resource' => 'customers', 'action' => 'update'],
            ['module' => 'reference', 'resource' => 'customers', 'action' => 'view'],
            ['module' => 'reference', 'resource' => 'suppliers', 'action' => 'create'],
            ['module' => 'reference', 'resource' => 'suppliers', 'action' => 'delete'],
            ['module' => 'reference', 'resource' => 'suppliers', 'action' => 'update'],
            ['module' => 'reference', 'resource' => 'suppliers', 'action' => 'view'],
            ['module' => 'sales', 'resource' => 'sales', 'action' => 'create'],
            ['module' => 'sales', 'resource' => 'sales', 'action' => 'update'],
            ['module' => 'sales', 'resource' => 'sales', 'action' => 'view'],
            ['module' => 'system', 'resource' => 'permissions', 'action' => 'create'],
            ['module' => 'system', 'resource' => 'permissions', 'action' => 'delete'],
            ['module' => 'system', 'resource' => 'permissions', 'action' => 'update'],
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
                    'description' => ucfirst($permission['action']).' '.ucfirst($permission['resource']),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
