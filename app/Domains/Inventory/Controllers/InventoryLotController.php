<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Requests\StoreInventoryLotRequest;
use App\Domains\Inventory\Resources\InventoryLotResource;
use App\Domains\Inventory\Services\InventoryLotService;
use App\Domains\Shared\Controllers\BaseApiController;

class InventoryLotController extends BaseApiController
{
    public function __construct(private InventoryLotService $service) {}

    public function store(StoreInventoryLotRequest $request)
    {
        $this->authorizeAbility($request, 'inventory.lots.create');
        $lot = $this->service->receive($request->validated(), $request->user()->id);

        return $this->created(new InventoryLotResource($lot), 'Inventory ownership lot received successfully.');
    }
}
