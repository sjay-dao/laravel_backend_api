<?php

namespace App\Domains\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'measurement_type' => $this->measurement_type,
            'is_base' => $this->is_base,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}