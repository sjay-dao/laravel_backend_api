<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Repositories\InventoryCategoryRepository;
use App\Domains\Shared\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Model;

class InventoryCategoryService extends BaseCrudService
{
    public function __construct(
        InventoryCategoryRepository $repository
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