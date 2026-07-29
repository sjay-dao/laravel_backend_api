<?php

namespace App\Domains\Reference\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lookup_type_id' => $this->lookup_type_id,
            'type' => $this->type?->code,

            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,

            'value' => $this->value,
            'color' => $this->color,
            'icon' => $this->icon,
            'metadata' => $this->metadata,

            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}