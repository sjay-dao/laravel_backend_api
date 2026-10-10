<?php

namespace App\Domains\System\Controllers;

use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\System\Models\Permission;
use App\Domains\System\Requests\StorePermissionRequest;
use App\Domains\System\Requests\UpdatePermissionRequest;
use App\Domains\System\Requests\SyncPermissionRolesRequest;
use App\Domains\System\Resources\PermissionResource;
use App\Domains\System\Resources\RoleResource;
use App\Domains\System\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends BaseApiController
{
    public function __construct(
        protected PermissionService $service
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'system.permissions.view');
        $permissions = $this->service->paginate(
            $request->integer('per_page', 15),
            $request->string('search')->toString()
        );

        return $this->paginated(
            $permissions,
            PermissionResource::class,
            'Permissions retrieved successfully.'
        );
    }

    public function show(Request $request, Permission $permission)
    {
        $this->authorizeAbility($request, 'system.permissions.view');

        $permission->load('roles');

        return $this->resource(
            new PermissionResource($permission),
            'Permission retrieved successfully.'
        );
    }

    public function store(StorePermissionRequest $request)
    {
        $this->authorizeAbility($request, 'system.permissions.create');

        $permission = $this->service->create(
            $request->validated()
        );

        return $this->created(
            new PermissionResource($permission),
            'Permission created successfully.'
        );
    }

    public function update(
        UpdatePermissionRequest $request,
        Permission $permission
    ) {
        $this->authorizeAbility($request, 'system.permissions.update');

        $permission = $this->service->update(
            $permission,
            $request->validated()
        );

        return $this->resource(
            new PermissionResource($permission),
            'Permission updated successfully.'
        );
    }

    public function destroy(Request $request, Permission $permission)
    {
        $this->authorizeAbility($request, 'system.permissions.delete');

        $this->service->delete($permission);

        return $this->deleted(
            'Permission deleted successfully.'
        );
    }

    public function roles(Request $request, Permission $permission)
    {
        $this->authorizeAbility($request, 'system.permissions.view');
        $permission->load('roles');

        return $this->success([
            'permission' => new PermissionResource($permission),
            'roles' => RoleResource::collection(
                $permission->roles
            ),
        ]);
    }

    public function syncRoles(
        SyncPermissionRolesRequest $request,
        Permission $permission
    ) {
        $this->authorizeAbility($request, 'system.permissions.assign');

        $permission = $this->service->assignRoles(
            $permission,
            $request->validated()['role_ids']
        );

        return $this->success(
            new PermissionResource($permission),
            'Roles updated successfully.'
        );
    }

    public function toggleStatus(Request $request, Permission $permission)
    {
        $this->authorizeAbility($request, 'system.permissions.view');
        $permission = $this->service->toggleStatus($permission);

        return $this->success(
            new PermissionResource($permission),
            'Permission status updated successfully.'
        );
    }

    public function options(Request $request)
    {
        $this->authorizeAbility($request, 'system.permissions.view');

        return $this->success(
            $this->service->options()
        );
    }
}