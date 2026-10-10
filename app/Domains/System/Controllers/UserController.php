<?php

namespace App\Domains\System\Controllers;

use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\System\Models\User;
use App\Domains\System\Requests\StoreUserRequest;
use App\Domains\System\Requests\SyncUserRolesRequest;
use App\Domains\System\Requests\UpdateUserRequest;
use App\Domains\System\Resources\RoleResource;
use App\Domains\System\Resources\UserResource;
use App\Domains\System\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    public function __construct(
        protected UserService $service
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'system.users.view');
        
        $users = $this->service->paginate(
            $request->integer('per_page', 15),
            $request->search
        );

        return $this->paginated(
            $users,
            UserResource::class,
            'Users retrieved successfully.'
        );
    }

    public function show(Request $request, User $user)
    {
        $this->authorizeAbility($request, 'system.users.view');
        return $this->resource(
            new UserResource($user->load('roles'))
        );
    }

    public function store(StoreUserRequest $request)
    {
       $this->authorizeAbility($request, 'system.users.create');
    
        $user = $this->service->create(
            $request->validated()
        );

        return $this->created(
            new UserResource($user)
        );
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ) {
        $this->authorizeAbility($request, 'system.users.update');

        $user = $this->service->update(
            $user,
            $request->validated()
        );

        return $this->resource(
            new UserResource($user)
        );
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeAbility($request, 'system.users.delete');

        $this->service->delete($user);

        return $this->deleted();
    }

    public function options(Request $request)
    {
        $this->authorizeAbility($request, 'system.users.view');

        return $this->success(
            $this->service->options()
        );
    }

    public function roles(Request $request, User $user)
    {
        $this->authorizeAbility($request, 'system.users.view');

        $user->load('roles');

        return $this->success([
            'user' => new UserResource($user),
            'roles' => RoleResource::collection(
                $user->roles
            ),
        ]);
    }

    public function syncRoles(
        SyncUserRolesRequest $request,
        User $user
    ) {
        $this->authorizeAbility($request, 'system.users.view');

        $user = $this->service->assignRoles(
            $user,
            $request->validated()['role_ids']
        );

        return $this->success(
            new UserResource($user),
            'Roles updated successfully.'
        );
    }
}