<?php

namespace App\Domains\Reference\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $barangay = $this->whenLoaded('barangay');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'barangay_id' => $this->barangay_id,

            'barangay' => $barangay ? [
                'id' => $barangay->id,
                'code' => $barangay->code,
                'name' => $barangay->description,
            ] : null,

            'branch_type' => $this->branch_type,
            'branch_category' => $this->branch_category,
            'service_bay_count' => $this->service_bay_count,
            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}