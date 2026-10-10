<?php

namespace App\Domains\System\Services;

use App\Domains\Shared\Services\BaseCrudService;
use App\Domains\System\Models\Role;
use App\Domains\System\Repositories\RoleRepository;

class RoleService extends BaseCrudService
{
    public function __construct(RoleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function assignPermissions(
    Role $role,
    array $permissionIds
    ): Role {

        $role->permissions()->sync($permissionIds);

        return $role->load('permissions');
    }

    public function options()
    {
        return $this->repository
            ->all()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            });
    }

    public function create(array $data): Role
    {
        $permissionIds = $data['permission_ids'] ?? [];

        unset($data['permission_ids']);

        $role = $this->repository->create($data);

        if (!empty($permissionIds)) {
            $role->permissions()->sync($permissionIds);
        }

        return $role->load('permissions');
    }

    public function update(Role $role, array $data): Role
    {
        $permissionIds = $data['permission_ids'] ?? null;

        unset($data['permission_ids']);

        $role = $this->repository->update(
            $role,
            $data
        );

        if ($permissionIds !== null) {
            $role->permissions()->sync($permissionIds);
        }

        return $role->load('permissions');
    }
}