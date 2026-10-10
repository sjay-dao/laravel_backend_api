<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvidenceReconciliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evidence_record_id' => $this->evidence_record_id,
            'inventory_object_id' => $this->inventory_object_id,
            'inventory_object_unit_id' => $this->inventory_object_unit_id,
            'relationship_type' => $this->relationship_type,
            'reconciliation_status' => $this->reconciliation_status,
            'notes' => $this->notes,
            'rationale' => $this->rationale,
            'confidence' => $this->confidence,
            'reconciled_at' => $this->reconciled_at?->toIso8601String(),
            'superseded_at' => $this->superseded_at?->toIso8601String(),
            'inventory_object' => $this->whenLoaded('inventoryObject', function () {
                return $this->inventoryObject ? [
                    'id' => $this->inventoryObject->id,
                    'code' => $this->inventoryObject->code,
                    'name' => $this->inventoryObject->name,
                    'brand' => $this->inventoryObject->brand,
                    'variant' => $this->inventoryObject->variant,
                    'packaging_description' => $this->inventoryObject->packaging_description,
                    'specification' => $this->inventoryObject->specification,
                ] : null;
            }),
            'inventory_object_unit' => $this->whenLoaded('inventoryObjectUnit', function () {
                return $this->inventoryObjectUnit ? [
                    'id' => $this->inventoryObjectUnit->id,
                    'conversion_factor' => $this->inventoryObjectUnit->conversion_factor,
                    'unit' => $this->inventoryObjectUnit->relationLoaded('unit')
                        ? [
                            'id' => $this->inventoryObjectUnit->unit?->id,
                            'code' => $this->inventoryObjectUnit->unit?->code,
                            'name' => $this->inventoryObjectUnit->unit?->name,
                            'symbol' => $this->inventoryObjectUnit->unit?->symbol,
                        ]
                        : null,
                ] : null;
            }),
            'reconciler' => $this->whenLoaded('reconciler', function () {
                return $this->reconciler ? [
                    'id' => $this->reconciler->id,
                    'name' => $this->reconciler->name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
