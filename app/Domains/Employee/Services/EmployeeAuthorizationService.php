<?php

namespace App\Domains\Employee\Services;

use App\Domains\System\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeAuthorizationService
{
    public function allows(User $user, string $permission): bool
    {
        $roles = $user->roles()
            ->pluck('code');

        return $roles->contains('admin')
            || DB::table('employee_role_permissions')
                ->whereIn('role_code', $roles)
                ->where('permission', $permission)
                ->exists();
    }
}
