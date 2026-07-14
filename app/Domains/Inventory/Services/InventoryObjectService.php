<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Repositories\InventoryObjectRepository;
use App\Domains\Inventory\Repositories\InventoryObjectUnitRepository;
use App\Domains\Shared\Services\BaseCrudService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class InventoryObjectService extends BaseCrudService
{
    protected InventoryObjectUnitRepository $inventoryObjectUnitRepository;

    public function __construct(
        InventoryObjectRepository $repository,
        InventoryObjectUnitRepository $inventoryObjectUnitRepository
    ) {
        $this->repository = $repository;
        $this->inventoryObjectUnitRepository = $inventoryObjectUnitRepository;
    }

    public function create(array $data): InventoryObject
    {
        return DB::transaction(function () use ($data) {

            $inventoryObject = $this->repository->create($data);

            $this->inventoryObjectUnitRepository->firstOrCreate(
                [
                    'inventory_object_id' => $inventoryObject->id,
                    'unit_id' => $inventoryObject->unit_id,
                ],
                [
                    'conversion_factor' => 1,
                ]
            );

            return $this->repository->findById($inventoryObject->id);
        });
    }

    public function update(Model $model, array $data): InventoryObject
    {
        /** @var InventoryObject $inventoryObject */
        $inventoryObject = $model;

        return DB::transaction(function () use ($inventoryObject, $data) {

            $oldUnitId = $inventoryObject->unit_id;

            $updated = $this->repository->update(
                $inventoryObject,
                $data
            );

            if (
                isset($data['unit_id']) &&
                $oldUnitId !== $data['unit_id']
            ) {
                $this->inventoryObjectUnitRepository->firstOrCreate(
                    [
                        'inventory_object_id' => $updated->id,
                        'unit_id' => $data['unit_id'],
                    ],
                    [
                        'conversion_factor' => 1,
                    ]
                );
            }

            return $this->repository->findById($updated->id);
        });
    }
}