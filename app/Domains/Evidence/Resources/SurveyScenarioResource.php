<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyScenarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scenario_code' => $this->scenario_code,
            'inventory_object' => $this->whenLoaded('inventoryObject', function () {
                return [
                    'id' => $this->inventoryObject->id,
                    'code' => $this->inventoryObject->code,
                    'name' => $this->inventoryObject->name,
                    'brand' => $this->inventoryObject->brand,
                    'variant' => $this->inventoryObject->variant,
                    'packaging_description' => $this->inventoryObject->packaging_description,
                    'specification' => $this->inventoryObject->specification,
                ];
            }),
            'inventory_object_unit' => $this->whenLoaded('inventoryObjectUnit', function () {
                return [
                    'id' => $this->inventoryObjectUnit->id,
                    'conversion_factor' => $this->inventoryObjectUnit->conversion_factor,
                    'unit' => $this->inventoryObjectUnit->relationLoaded('unit')
                        ? [
                            'id' => $this->inventoryObjectUnit->unit?->id,
                            'code' => $this->inventoryObjectUnit->unit?->code,
                            'name' => $this->inventoryObjectUnit->unit?->name,
                        ]
                        : null,
                ];
            }),
            'proposed_price' => $this->proposed_price,
            'currency_code' => $this->currency_code,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
