<?php

namespace App\Domains\System\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'permission_count' => $this->whenCounted('permissions'),
            'user_count' => $this->whenCounted('users'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}