<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierObservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->supplier->id,
                    'code' => $this->supplier->code,
                    'name' => $this->supplier->name,
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
            'purchase_price' => $this->purchase_price,
            'currency_code' => $this->currency_code,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'available_quantity' => $this->available_quantity,
            'product_specification' => $this->product_specification,
            'freight_amount' => $this->freight_amount,
            'freight_terms' => $this->freight_terms,
            'payment_terms' => $this->payment_terms,
            'lead_time_days' => $this->lead_time_days,
            'discount_amount' => $this->discount_amount,
            'discount_description' => $this->discount_description,
            'supplier_location' => $this->supplier_location,
            'provenance' => new EvidenceRecordResource($this->whenLoaded('evidence')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
