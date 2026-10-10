<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimulatedPurchaseEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'survey_id' => $this->survey_id,
            'survey_respondent_id' => $this->survey_respondent_id,
            'survey_response_id' => $this->survey_response_id,
            'survey_scenario_id' => $this->survey_scenario_id,
            'source_type' => $this->source_type,
            'epistemic_status' => $this->epistemic_status,
            'would_purchase' => $this->would_purchase,
            'proposed_price' => $this->proposed_price,
            'currency_code' => $this->currency_code,
            'unit_snapshot' => $this->unit_snapshot,
            'simulated_quantity' => $this->simulated_quantity,
            'base_quantity' => $this->base_quantity,
            'conversion_factor' => $this->conversion_factor,
            'frequency_raw' => $this->frequency_raw,
            'estimated_frequency_per_month' => $this->estimated_frequency_per_month,
            'alternative_inventory_object' => $this->whenLoaded('alternativeInventoryObject', function () {
                return $this->alternativeInventoryObject ? [
                    'id' => $this->alternativeInventoryObject->id,
                    'code' => $this->alternativeInventoryObject->code,
                    'name' => $this->alternativeInventoryObject->name,
                ] : null;
            }),
            'decision_reason' => $this->decision_reason,
            'derived_payload' => $this->derived_payload,
            'simulated_at' => $this->simulated_at?->toIso8601String(),
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
                        ]
                        : null,
                ];
            }),
            'respondent' => $this->whenLoaded('response', function () {
                if (! $this->response?->relationLoaded('respondent')) {
                    return null;
                }

                return $this->response->respondent ? [
                    'id' => $this->response->respondent->id,
                    'respondent_code' => $this->response->respondent->respondent_code,
                ] : null;
            }),
            'provenance' => new EvidenceRecordResource($this->whenLoaded('evidence')),
        ];
    }
}
