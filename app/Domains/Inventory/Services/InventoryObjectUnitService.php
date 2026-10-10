<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Inventory\Repositories\InventoryObjectUnitRepository;
use Illuminate\Support\Facades\DB;
use App\Domains\Shared\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Model;

class InventoryObjectUnitService extends BaseCrudService
{
    public function __construct(
        InventoryObjectUnitRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function paginate(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): InventoryObjectUnit
    {
        return DB::transaction(function () use ($data) {

            return $this->repository->create($data);

        });
    }

    public function update(
        InventoryObjectUnit $inventoryObjectUnit,
        array $data
    ): InventoryObjectUnit {

        return DB::transaction(function () use (
            $inventoryObjectUnit,
            $data
        ) {

            return $this->repository->update(
                $inventoryObjectUnit,
                $data
            );

        });
    }

    public function delete(Model $model): bool
    {
        /** @var InventoryObjectUnit $model */

        return DB::transaction(function () use ($model) {
            return $this->repository->delete($model);
        });
    }
}