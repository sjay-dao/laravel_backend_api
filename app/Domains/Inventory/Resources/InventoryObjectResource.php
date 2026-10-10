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

            'inventory_category_id' => $this->inventory_category_id,

            'unit_id' => $this->unit_id,

            'brand' => $this->brand,

            'variant' => $this->variant,

            'packaging_description' => $this->packaging_description,

            'specification' => $this->specification,

            'track_inventory' => $this->track_inventory,

            'is_sellable' => $this->is_sellable,

            'is_active' => $this->is_active,

            'retail_price_cents' => $this->retail_price_cents,

            'low_stock_threshold' => $this->low_stock_threshold,

            'wholesale_price_tiers' => $this->whenLoaded('wholesalePriceTiers'),

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
