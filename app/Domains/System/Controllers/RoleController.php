<?php

namespace App\Domains\System\Controllers;

use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\System\Models\Role;
use App\Domains\System\Requests\StoreRoleRequest;
use App\Domains\System\Requests\UpdateRoleRequest;
use App\Domains\System\Resources\RoleResource;
use App\Domains\System\Services\RoleService;
use App\Domains\System\Resources\PermissionResource;
use App\Domains\System\Requests\SyncRolePermissionsRequest;
use Illuminate\Http\Request;
use App\Domains\System\Models\Permission;

class RoleController extends BaseApiController
{
    public function __construct(
        protected RoleService $service
    ) {
    }

    public function index(Request $request)
    {
       $this->authorizeAbility($request, 'system.roles.view');

        $roles = $this->service->paginate(
            $request->integer('per_page', 15)
        );

        return $this->paginated(
            $roles,
            RoleResource::class,
            'Roles retrieved successfully.'
        );
    }

    public function show(Request $request, Role $role)
    {
        $this->authorizeAbility($request, 'system.roles.view');
        return $this->resource(
            new RoleResource($role),
            'Role retrieved successfully.'
        );
    }

    public function store(StoreRoleRequest $request)
    {
        $this->authorizeAbility($request, 'system.roles.create');

        $role = $this->service->create(
            $request->validated()
        );

        return $this->created(
            new RoleResource(
                $role->load('permissions')
            ),
            'Role created successfully.'
        );
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role
    ) {
        $this->authorizeAbility($request, 'system.roles.update');


        $role = $this->service->update(
            $role,
            $request->validated()
        );

        return $this->resource(
            new RoleResource(
                $role->load('permissions')
            ),
            'Role updated successfully.'
        );
    }

    public function destroy(Request $request, Role $role)
    {
        $this->authorizeAbility($request, 'system.roles.delete');

        $this->service->delete($role);

        return $this->deleted(
            'Role deleted successfully.'
        );
    }

    public function permissions(Request $request, Role $role)
    {
        $this->authorizeAbility($request, 'system.roles.view');

        $role->load('permissions');

        return $this->success([
            'role' => new RoleResource($role),

            'permissions' => PermissionResource::collection(
                Permission::where('is_active', true)
                    ->orderBy('module')
                    ->orderBy('resource')
                    ->orderBy('action')
                    ->get()
            ),

            'assigned_permission_ids' => $role->permissions
                ->pluck('id')
                ->values(),
        ]);
    }

    public function syncPermissions(
    SyncRolePermissionsRequest $request,
    Role $role
    ) {
        $this->authorizeAbility($request, 'system.roles.view');

        $role = $this->service->assignPermissions(
            $role,
            $request->validated()['permission_ids']
        );

        return $this->success(
            new RoleResource($role),
            'Permissions updated successfully.'
        );
    }

    public function options(Request $request)
    {
        $this->authorizeAbility($request, 'system.roles.view');

        return $this->success(
            $this->service->options()
        );
    }

    
}