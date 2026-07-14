<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Models\Unit;
use App\Domains\Inventory\Requests\StoreUnitRequest;
use App\Domains\Inventory\Requests\UpdateUnitRequest;
use App\Domains\Inventory\Resources\UnitResource;
use App\Domains\Inventory\Services\UnitService;
use App\Domains\Shared\Controllers\BaseApiController;

class UnitController extends BaseApiController
{
    /**
     * @var UnitService
     */
    protected UnitService $service;

    public function __construct(UnitService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->paginated(
            $this->service->paginate(),
            UnitResource::class,
            'Units retrieved successfully.'
        );
    }

    public function create(StoreUnitRequest $request)
    {
        return $this->created(
            new UnitResource(
                $this->service->create(
                    $request->validated()
                )
            ),
            'Unit created successfully.'
        );
    }

    public function show(
        Unit $unit
    )
    {
        return $this->resource(
            new UnitResource($unit)
        );
    }

    public function update(
        UpdateUnitRequest $request,
        Unit $unit
    )
    {
        return $this->resource(
            new UnitResource(
                $this->service->update(
                    $unit,
                    $request->validated()
                )
            ),
            'Unit updated successfully.'
        );
    }

    public function delete(
        Unit $unit
    )
    {
        $this->service->delete($unit);

        return $this->deleted(
            'Unit deleted successfully.'
        );
    }
}