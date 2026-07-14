<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Repositories\UnitRepository;
use App\Domains\Shared\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Model;

class UnitService extends BaseCrudService
{
    public function __construct(
        UnitRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(Model $model, array $data)
    {
        return $this->repository->update($model, $data);
    }
}