<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id'=>$this->id,

            'code'=>$this->code,

            'branch_id'=>$this->branch_id,

            'name'=>$this->name,

            'description'=>$this->description,

            'active'=>$this->is_active,

        ];
    }
}
