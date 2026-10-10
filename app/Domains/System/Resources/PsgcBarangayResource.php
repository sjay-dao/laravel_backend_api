<?php

namespace App\Domains\System\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PsgcBarangayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,

            'label' => collect([
                $this->description,
                $this->cityMun?->description,
                $this->province?->description,
            ])
            ->filter()
            ->implode(', '),

            'barangay' => $this->description,

            'city' => $this->cityMun?->description,

            'province' => $this->province?->description,

            'region' => $this->region?->description,
        ];
    }
}