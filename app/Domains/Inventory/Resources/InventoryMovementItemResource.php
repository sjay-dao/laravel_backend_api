<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'inventory_object' => [
                'id' => $this->inventoryObject?->id,
                'name' => $this->inventoryObject?->name,
            ],

            'unit' => [
                'id' => $this->unit?->id,
                'name' => $this->unit?->name,
                'symbol' => $this->unit?->symbol,
            ],

            'quantity' => $this->quantity,

            'unit_conversion_factor' => $this->unit_conversion_factor,

            'base_quantity' => $this->base_quantity,

            'remarks' => $this->remarks,

        ];
    }
}