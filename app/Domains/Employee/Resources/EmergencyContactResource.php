<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyContactResource extends JsonResource
{
    public function toArray($request): array { return ['id' => $this->id, 'name' => $this->name, 'relationship' => $this->relationship, 'mobile_number' => $this->mobile_number, 'phone_number' => $this->phone_number, 'address' => $this->address, 'is_primary' => $this->is_primary]; }
}
