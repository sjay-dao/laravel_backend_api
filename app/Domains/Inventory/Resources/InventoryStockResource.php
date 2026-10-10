<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'inventory_object_id' => $this['inventory_object_id'],

            'code' => $this['code'],

            'name' => $this['name'],

            'base_unit' => $this['base_unit'],

            'on_hand' => $this['on_hand'],

            'reserved' => $this['reserved'],

            'available' => $this['available'],

        ];
    }
}