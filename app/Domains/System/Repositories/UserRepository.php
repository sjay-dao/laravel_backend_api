<?php

namespace App\Domains\System\Repositories;

use App\Domains\Shared\Repositories\BaseRepository;
use App\Domains\System\Models\User;

class UserRepository extends BaseRepository
{
    protected array $with = [
        'roles',
    ];

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, ?string $search = null)
    {
        return $this->query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate($perPage);
    }

    public function options()
    {
        return $this->model
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }
}