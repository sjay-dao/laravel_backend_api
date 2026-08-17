<?php

namespace App\Domains\System\Services;

use App\Domains\Shared\Services\BaseCrudService;
use App\Domains\System\Models\Permission;
use App\Domains\System\Repositories\PermissionRepository;

class PermissionService extends BaseCrudService
{
    public function __construct(
        protected PermissionRepository $permissionRepository
    ) {
        $this->repository = $permissionRepository;
    }

    public function create(array $data): Permission
    {
        return $this->repository->create($data);
    }

    public function update(Permission $permission, array $data): Permission
    {
        return $this->repository->update($permission, $data);
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ) {
        return $this->permissionRepository
            ->paginate($perPage, $search);
    }

    public function options()
    {
        return $this->permissionRepository->options();
    }

    public function toggleStatus(Permission $permission): Permission
    {
        return $this->repository->update($permission, [
            'is_active' => !$permission->is_active,
        ]);
    }

    public function assignRoles(
        Permission $permission,
        array $roleIds
    ): Permission {
        $permission->roles()->sync($roleIds);

        return $permission->load('roles');
    }
}