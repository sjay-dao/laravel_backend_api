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
            'inventory_object_unit' => [

                'id' => $this->inventoryObjectUnit?->id,

                'conversion_factor' =>
                    $this->inventoryObjectUnit?->conversion_factor,

                'inventory_object' => [

                    'id' =>
                        $this->inventoryObjectUnit?->inventoryObject?->id,

                    'code' =>
                        $this->inventoryObjectUnit?->inventoryObject?->code,

                    'name' =>
                        $this->inventoryObjectUnit?->inventoryObject?->name,

                ],

                'unit' => [

                    'id' =>
                        $this->inventoryObjectUnit?->unit?->id,

                    'code' =>
                        $this->inventoryObjectUnit?->unit?->code,

                    'symbol' =>
                        $this->inventoryObjectUnit?->unit?->symbol,

                ],

            ],
            
            'quantity' => $this->quantity,

            'unit_conversion_factor' => $this->unit_conversion_factor,

            'base_quantity' => $this->base_quantity,

            'remarks' => $this->remarks,

        ];
    }
}