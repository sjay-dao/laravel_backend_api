<?php
namespace App\Domains\Reference\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'address' => $this->address,
            'barangay_id' => $this->barangay_id,
            'barangay' => $this->whenLoaded(
                'barangay',
                function () {
                    return [
                        'id' => $this->barangay->id,
                        'code' => $this->barangay->code,
                        'description' => $this->barangay->description,
                        'city_mun' => $this->barangay->cityMun ? [
                            'id' => $this->barangay->cityMun->id,
                            'code' => $this->barangay->cityMun->code,
                            'description' => $this->barangay->cityMun->description,
                            'province' => $this->barangay->cityMun->province ? [
                                'id' => $this->barangay->cityMun->province->id,
                                'code' => $this->barangay->cityMun->province->code,
                                'description' => $this->barangay->cityMun->province->description,
                                'region' => $this->barangay->cityMun->province->region ? [
                                    'id' => $this->barangay->cityMun->province->region->id,
                                    'code' => $this->barangay->cityMun->province->region->code,
                                    'description' => $this->barangay->cityMun->province->region->description,
                                ] : null,
                            ] : null,
                        ] : null,
                    ];
                }
            ),
            'tin' => $this->tin,
            'remarks' => $this->remarks,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}