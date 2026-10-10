<?php

namespace App\Domains\System\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'resource' => $this->resource,
            'action' => $this->action,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'role_count' => $this->whenCounted('roles'),
            'roles' => RoleResource::collection(
                $this->whenLoaded('roles')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}