<?php

namespace App\Domains\System\Repositories;

use App\Domains\Shared\Repositories\BaseRepository;
use App\Domains\System\Models\Role;

class RoleRepository extends BaseRepository
{
    protected array $with = [
        'permissions',
        'users',
    ];

    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model
            ->newQuery()
            ->withCount(['permissions', 'users'])
            ->paginate($perPage);
    }
}