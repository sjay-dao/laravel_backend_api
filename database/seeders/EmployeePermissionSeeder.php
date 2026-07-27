<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = ['employee.view', 'employee.create', 'employee.update', 'employee.delete', 'employee.transactions.manage', 'employee.reports.view'];
        $rows = [];
        foreach (['admin', 'manager'] as $role) { foreach ($permissions as $permission) { $rows[] = ['role_code' => $role, 'permission' => $permission, 'created_at' => now(), 'updated_at' => now()]; } }
        foreach (['branch_staff'] as $role) { foreach (['employee.view', 'employee.transactions.manage'] as $permission) { $rows[] = ['role_code' => $role, 'permission' => $permission, 'created_at' => now(), 'updated_at' => now()]; } }
        DB::table('employee_role_permissions')->upsert($rows, ['role_code', 'permission'], ['updated_at']);
    }
}
