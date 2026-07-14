<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryObjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'code' => $this->code,

            'name' => $this->name,

            'track_inventory' => $this->track_inventory,

            'is_sellable' => $this->is_sellable,

            'is_active' => $this->is_active,

            'category' => new InventoryCategoryResource(
                $this->whenLoaded('category')
            ),

            'base_unit' => new UnitResource(
                $this->whenLoaded('baseUnit')
            ),

            'units' => InventoryObjectUnitResource::collection(
                $this->whenLoaded('units')
            ),

        ];
    }
}