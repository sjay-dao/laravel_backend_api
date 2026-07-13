<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchSelectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => $this->name,
            'code' => $this->code,
            'is_active' => $this->is_active,

            'delivery_address' => [
                'id' => optional($this->address)->id,
                'label' => optional($this->address)->label,

                'contact_person' => optional($this->address)->contact_person,
                'contact_number' => optional($this->address)->contact_number,

                'address_line_1' => optional($this->address)->address_line_1,
                'address_line_2' => optional($this->address)->address_line_2,
                'postal_code' => optional($this->address)->postal_code,

                'latitude' => optional($this->address)->latitude,
                'longitude' => optional($this->address)->longitude,

                'barangay' => optional(optional($this->address)->barangay)->name,
                'city_mun' => optional(optional(optional($this->address)->barangay)->cityMun)->name,
                'province' => optional(optional(optional(optional($this->address)->barangay)->cityMun)->province)->name,
                'region' => optional(optional(optional(optional(optional($this->address)->barangay)->cityMun)->province)->region)->name,
            ],
        ];
    }
}