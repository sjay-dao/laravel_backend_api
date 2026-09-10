<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvidenceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_object_id' => $this->inventory_object_id,
            'evidence_scenario_id' => $this->evidence_scenario_id,
            'source_type' => $this->source_type,
            'epistemic_status' => $this->epistemic_status,
            'collection_method' => $this->collection_method,
            'source_reference' => $this->source_reference,
            'source_entity_type' => $this->source_entity_type,
            'source_entity_id' => $this->source_entity_id,
            'observed_at' => $this->observed_at?->toIso8601String(),
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'location_name' => $this->location_name,
            'raw_payload' => $this->raw_payload,
            'context_payload' => $this->context_payload,
            'notes' => $this->notes,
            'collector' => $this->whenLoaded('collector', function () {
                return $this->collector ? [
                    'id' => $this->collector->id,
                    'name' => $this->collector->name,
                ] : null;
            }),
            'supplier_observation' => new SupplierObservationResource(
                $this->whenLoaded('supplierObservation')
            ),
            'market_observation' => new MarketObservationResource(
                $this->whenLoaded('marketObservation')
            ),
            'simulated_purchase_event' => new SimulatedPurchaseEventResource(
                $this->whenLoaded('simulatedPurchaseEvent')
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
