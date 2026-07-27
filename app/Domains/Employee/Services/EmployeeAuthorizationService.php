<?php

namespace App\Domains\Employee\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeAuthorizationService
{
    public function allows(User $user, string $permission): bool
    {
        $roles = collect((array) $user->roles)->filter()->values();
        if ($user->role_id) { $role = DB::table('roles')->where('id', $user->role_id)->value('code'); if ($role) { $roles->push($role); } }
        return $roles->contains('admin') || DB::table('employee_role_permissions')->whereIn('role_code', $roles->all())->where('permission', $permission)->exists();
    }
}
