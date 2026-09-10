<?php

namespace App\Domains\Evidence\Services;

use App\Domains\Evidence\Models\EvidenceScenario;
use App\Domains\Evidence\Models\SimulatedPurchaseEvent;
use App\Domains\Shared\Analytics\BusinessEventProjection;
use Illuminate\Support\Collection;

class SimulatedPurchaseProjection
{
    /** Require a scenario explicitly; no accidental cross-scenario totals. */
    public function forScenario(EvidenceScenario $scenario): Collection
    {
        return SimulatedPurchaseEvent::query()
            ->whereHas('evidence', fn ($query) => $query->where('evidence_scenario_id', $scenario->id))
            ->with('evidence')->get()
            ->map(fn (SimulatedPurchaseEvent $event) => $this->project($event));
    }

    public function project(SimulatedPurchaseEvent $event): BusinessEventProjection
    {
        $event->loadMissing('evidence');
        $evidence = $event->evidence;

        return new BusinessEventProjection(
            event_type: 'consumer_purchase_intent',
            source_record: 'simulated_purchase_events:'.$event->id,
            inventory_object_id: $evidence->inventory_object_id,
            counterparty: ['type' => 'survey_respondent', 'id' => $event->survey_respondent_id],
            quantity: $event->simulated_quantity,
            unit: [
                'inventory_object_unit_id' => $event->inventory_object_unit_id,
                'snapshot' => $event->unit_snapshot,
                'base_quantity' => $event->base_quantity,
                'conversion_factor' => $event->conversion_factor,
            ],
            unit_amount: $event->proposed_price,
            total_amount: null,
            amount_basis: 'proposed_selling_price_per_selected_unit',
            currency: $event->currency_code,
            occurred_at: $event->simulated_at?->toIso8601String(),
            source: $event->source_type,
            epistemic_status: $event->epistemic_status,
            scenario: $evidence->evidence_scenario_id === null
                ? null : 'evidence_scenarios:'.$evidence->evidence_scenario_id,
            provenance: [
                'evidence_record_id' => $evidence->id,
                'survey_response_id' => $event->survey_response_id,
                'survey_scenario_id' => $event->survey_scenario_id,
                'source_reference' => $evidence->source_reference,
                'collection_method' => $evidence->collection_method,
                'observed_at' => $evidence->observed_at?->toIso8601String(),
                'recorded_at' => $evidence->recorded_at?->toIso8601String(),
                'would_purchase' => $event->would_purchase,
                'raw_evidence_reference' => 'evidence_records:'.$evidence->id,
                'derivation' => $event->derived_payload,
            ],
        );
    }
}
