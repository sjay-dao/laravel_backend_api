<?php

namespace App\Domains\System\Repositories;

use App\Domains\Shared\Repositories\BaseRepository;
use App\Domains\System\Models\Permission;

class PermissionRepository extends BaseRepository
{
    protected array $with = [
        'roles',
    ];

    public function __construct(Permission $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, ?string $search = null)
    {
        return $this->query()
            ->withCount('roles')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('module', 'like', "%{$search}%")
                        ->orWhere('resource', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->paginate($perPage);
    }

    public function options()
    {
        return $this->model
            ->select('id', 'code')
            ->orderBy('code')
            ->get();
    }
}