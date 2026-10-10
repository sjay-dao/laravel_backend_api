<?php

namespace App\Domains\Reference\Repositories;

use App\Domains\Reference\Models\Branch;
use App\Domains\Shared\Repositories\BaseRepository;

class BranchRepository extends BaseRepository
{
    protected array $with = [
        'barangay.cityMun.province.region',
    ];

    public function __construct(Branch $model)
    {
        $this->model = $model;
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ) {
        return $this->query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function options()
    {
        return $this->model
            ->active()
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
            ]);
    }
}