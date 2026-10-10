<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryObjectUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'inventory_object' => [
                'id' => $this->inventoryObject->id,
                'name' => $this->inventoryObject->name,
            ],

            'unit' => [
                'id' => $this->unit->id,
                'code'   => $this->unit?->code,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ],
            
            'conversion_factor' => $this->conversion_factor,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}