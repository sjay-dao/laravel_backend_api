<?php

namespace App\Domains\System\Services;

use App\Domains\System\Models\User;

class AuthorizationService
{
    /**
     * Check if user has a role.
     */
    public function hasRole(User $user, string $role): bool
    {
        return $user->roles()
            ->where('code', $role)
            ->exists();
    }

    /**
     * Check if user has a permission.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        return $user->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('code', $permission)
                    ->where('is_active', true);
            })
            ->exists();
    }

    /**
     * Alias of hasPermission()
     */
    public function can(User $user, string $permission): bool
    {
        return $this->hasPermission($user, $permission);
    }
}