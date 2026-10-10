<?php

namespace App\Domains\System\Services;

use App\Domains\Shared\Services\BaseCrudService;
use App\Domains\System\Models\User;
use App\Domains\System\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;


class UserService extends BaseCrudService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
        $this->repository = $userRepository;
    }

    public function create(array $data): User
    {
        $roleIds = $data['role_ids'] ?? [];

        unset($data['role_ids']);

        $data['password'] = Hash::make($data['password']);

        $user = $this->repository->create($data);

        if (!empty($roleIds)) {
            $user->roles()->sync($roleIds);
        }

        return $user->load('roles');
    }

    public function update(User $user, array $data): User
    {
        $roleIds = $data['role_ids'] ?? null;

        unset($data['role_ids']);

        if (isset($data['password'])) {

            if ($data['password']) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

        }

        $user = $this->repository->update($user, $data);

        if ($roleIds !== null) {
            $user->roles()->sync($roleIds);
        }

        return $user->load('roles');
    }

    public function assignRoles(User $user, array $roleIds): User
    {
        $user->roles()->sync($roleIds);

        return $user->load('roles');
    }

    public function options()
    {
        return $this->userRepository->options();
    }

    public function toggleStatus(User $user): User
    {
        $user = $this->repository->update($user, [
            'is_active' => !$user->is_active,
        ]);

        return $user;
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null
    )
    {
        return $this->repository->paginate(
            $perPage,
            $search
        );
    }


}