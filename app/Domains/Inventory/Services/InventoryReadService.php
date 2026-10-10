<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryLot;
use App\Domains\Inventory\Models\InventoryObject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventoryReadService
{
    public function productSummaries(int $perPage = 15): LengthAwarePaginator
    {
        return InventoryObject::query()
            ->with(['category:id,code,name', 'wholesalePriceTiers' => fn ($query) => $query->orderBy('min_quantity')])
            ->withSum('lots as total_available_quantity', 'quantity_available')
            ->withSum(['lots as owned_available_quantity' => fn ($query) => $query->where('ownership', 'OWNED')], 'quantity_available')
            ->withSum(['lots as consignment_available_quantity' => fn ($query) => $query->where('ownership', 'CONSIGNMENT')], 'quantity_available')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function productLots(InventoryObject $inventoryObject): Collection
    {
        return InventoryLot::query()
            ->where('inventory_object_id', $inventoryObject->id)
            ->with(['supplier:id,code,name', 'warehouse:id,code,name'])
            ->orderBy('received_date')
            ->orderBy('id')
            ->get();
    }
}
