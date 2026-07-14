<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryObjectUnitCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => InventoryObjectUnitResource::collection($this->collection),
        ];
    }
}