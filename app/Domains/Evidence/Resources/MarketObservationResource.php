<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketObservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
                    'inventory_object' => $this->inventoryObjectUnit->relationLoaded('inventoryObject')
                        ? [
                            'id' => $this->inventoryObjectUnit->inventoryObject?->id,
                            'code' => $this->inventoryObjectUnit->inventoryObject?->code,
                            'name' => $this->inventoryObjectUnit->inventoryObject?->name,
                            'brand' => $this->inventoryObjectUnit->inventoryObject?->brand,
                            'variant' => $this->inventoryObjectUnit->inventoryObject?->variant,
                            'packaging_description' => $this->inventoryObjectUnit->inventoryObject?->packaging_description,
                        ]
                        : null,
                ];
            }),
            'store_name' => $this->store_name,
            'channel' => $this->channel,
            'selling_price' => $this->selling_price,
            'currency_code' => $this->currency_code,
            'package_size' => $this->package_size,
            'availability_status' => $this->availability_status,
            'promotion' => $this->promotion,
            'provenance' => new EvidenceRecordResource($this->whenLoaded('evidence')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
