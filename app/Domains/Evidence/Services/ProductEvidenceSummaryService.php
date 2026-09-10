<?php

namespace App\Domains\Evidence\Services;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Models\SurveyScenario;
use App\Domains\Evidence\Resources\ReconciliationEvidenceResource;
use App\Domains\Inventory\Models\InventoryObject;
use Illuminate\Support\Collection;

/** Read-only evidence inventory. Rules v1 qualify inputs, never choose prices or calculate outcomes. */
class ProductEvidenceSummaryService
{
    private const PRICE_STATUSES = ['observed', 'reported', 'accounted'];

    public function build(InventoryObject $product, Collection $exact, Collection $comparable): array
    {
        $product->loadMissing(['baseUnit', 'units.unit']);
        $all = $exact->concat($comparable)->unique('id');
        $rows = $all->map(fn ($record) => $this->row($record, $exact->contains('id', $record->id)))->values();
        $suppliers = $rows->where('category', 'supplier')->values();
        $markets = $rows->where('category', 'market')->values();
        $events = $exact->pluck('simulatedPurchaseEvent')->filter()->unique('id')->values();
        $responses = $events->pluck('response')->filter()->unique('id');
        $scenarios = SurveyScenario::where('inventory_object_id', $product->id)->orderBy('id')->get();
        $tested = $events->map(fn ($event) => [
            'survey_scenario_id' => $event->survey_scenario_id,
            'unit_amount' => $event->proposed_price,
            'currency' => $event->currency_code,
            'inventory_object_unit_id' => $event->inventory_object_unit_id,
            'unit_snapshot' => $event->unit_snapshot,
        ])->unique(fn ($point) => json_encode($point))->values();

        $validUnits = $product->units->filter(fn ($unit) => $unit->unit !== null && is_numeric($unit->conversion_factor) && (float) $unit->conversion_factor > 0);
        $acquisition = $suppliers->filter(fn ($row) => $row['group'] === 'exact' && $row['qualified_price'] !== null
            && $validUnits->contains('id', $row['qualified_price']['inventory_object_unit_id']))->values();
        $marketPrices = $markets->filter(fn ($row) => $row['qualified_price'] !== null);
        // A compatible pair must use the same currency and unit; no guessed FX or package conversion.
        $paired = $acquisition->contains(fn ($supplier) => $marketPrices->contains(fn ($market) => $supplier['qualified_price']['currency'] === $market['qualified_price']['currency']
            && $supplier['qualified_price']['unit_code'] === $market['qualified_price']['unit_code']));
        $identity = filled($product->code) && filled($product->name);
        $checks = [
            'product_identity' => $this->check($identity ? 'AVAILABLE' : 'MISSING', 'Existing Inventory Object code and name are required.'),
            'acquisition_price' => $this->check($acquisition->isNotEmpty() ? 'AVAILABLE' : ($suppliers->isNotEmpty() ? 'PARTIAL' : 'MISSING'), 'Requires exact typed supplier price, explicit currency and a valid product UOM; observed/reported/accounted only. A quote is not actual purchased cost.'),
            'quantity_uom' => $this->check($validUnits->isNotEmpty() ? 'AVAILABLE' : 'MISSING', 'Requires a named product UOM with an explicit positive base conversion. Purchase quantity remains a manual scenario input.'),
            'market_context' => $this->check($marketPrices->isNotEmpty() ? 'AVAILABLE' : ($markets->isNotEmpty() ? 'PARTIAL' : 'MISSING'), 'Requires a supplied price, currency and unit with observed/reported/accounted status. Comparable context is labeled and never selected as the selling price.'),
            'consumer_signal' => $this->check($responses->isNotEmpty() ? 'AVAILABLE' : 'MISSING', $responses->isNotEmpty() ? 'Product-linked survey responses exist; stated intent does not establish monthly demand.' : 'NOT_COLLECTED: no product-linked responses. This is not evidence of zero demand.'),
            'freight_extras' => $this->check($acquisition->contains(fn ($row) => $row['commercial']['freight_amount'] !== null) ? 'PARTIAL' : 'MISSING', 'Freight and other acquisition extras are not a validated landed cost. Null freight is UNKNOWN, never zero; a recorded freight amount alone does not establish all extras.'),
            'scenario_ready_uom' => $this->check($paired ? 'AVAILABLE' : ($validUnits->isNotEmpty() ? 'PARTIAL' : 'MISSING'), 'Requires at least one qualified exact supplier and market-context price sharing currency and unit code; no automatic conversions.'),
            'operating_expenses' => $this->check('MISSING', 'NOT_COLLECTED by this product workflow. Supply explicit expense assumptions when constructing a full business forecast.'),
        ];
        // This readiness is only the starting product price/cost scenario, not a complete forecast.
        $ready = $identity && $acquisition->isNotEmpty() && $paired;
        $status = $ready ? 'READY_FOR_MANUAL_SCENARIO' : ($identity && $all->isNotEmpty() ? 'PARTIALLY_READY' : 'NOT_READY');
        $breakdown = [];
        foreach (EvidenceService::EPISTEMIC_STATUSES as $epistemic) {
            $breakdown[$epistemic] = ['exact' => $exact->where('epistemic_status', $epistemic)->count(), 'comparable' => $comparable->where('epistemic_status', $epistemic)->count()];
        }
        $coverage = [];
        foreach (['supplier', 'market', 'consumer', 'simulation'] as $category) {
            $subset = $rows->where('category', $category);
            $coverage[$category] = ['exact' => $subset->where('group', 'exact')->count(), 'comparable' => $subset->where('group', 'comparable')->count()];
        }
        // Consumer and simulation overlap intentionally: one counts raw responses, the other derived events.
        $coverage['consumer'] = ['exact' => $responses->count(), 'comparable' => 0];
        $coverage['simulation'] = ['exact' => $events->count(), 'comparable' => 0];

        return [
            'coverage' => $coverage,
            'relationships' => ['exact' => $exact->count(), 'comparable' => $comparable->count(),
                'unresolved' => app(EvidenceReconciliationService::class)->unresolvedCandidateCount($product->id),
                'unresolved_scope' => 'Unresolved external records explicitly listing this product as a candidate; hints are not usable evidence. Unassigned records without hints are only in Reconciliation.'],
            'supplier' => $suppliers,
            'market' => ['exact' => $markets->where('group', 'exact')->values(), 'comparable' => $markets->where('group', 'comparable')->values()],
            'consumer' => [
                'state' => $responses->isEmpty() ? 'NOT_COLLECTED' : 'AVAILABLE',
                'respondent_count' => $responses->pluck('survey_respondent_id')->filter()->unique()->count(),
                'raw_response_count' => $responses->count(), 'configured_scenario_count' => $scenarios->count(),
                'tested_scenario_count' => $events->pluck('survey_scenario_id')->filter()->unique()->count(),
                'simulated_event_count' => $events->count(), 'tested_price_points' => $tested,
                'demand_estimate' => null,
                'meaning' => 'Only responses traceable through this product\'s purchase-intent events are counted. Respondent IDs are not verified people. Configured scenarios are not tested until a response exists. No monthly demand estimate is inferred.',
            ],
            'simulated_events' => $events->map(fn ($event) => app(SimulatedPurchaseProjection::class)->project($event))->values(),
            'epistemic_breakdown' => $breakdown,
            'missing_evidence' => $checks,
            'readiness' => [
                'rules_version' => 'product-manual-scenario-v1', 'status' => $status,
                'forecast_status' => $all->isEmpty() ? 'NOT_READY' : 'PARTIALLY_READY',
                'forecast_reason' => 'Full Forecast inputs are incomplete: demand, operating expenses, growth and capital require independently qualified data or explicit scenario assumptions. This endpoint never declares a complete forecast ready.',
                'scope' => 'Start a manual product price/cost scenario only. This is not profitability, demand validation, supplier selection, or full Forecast readiness.',
                'rule' => 'Requires product code/name, a qualified exact supplier price with valid product UOM, and qualified exact or comparable market context with the same currency and unit. With linked evidence but unmet requirements: PARTIALLY_READY; otherwise NOT_READY.',
                'warnings' => [
                    $responses->isEmpty() ? 'Demand is unsupported: no consumer responses collected. An explicit demand assumption is required.' : 'Survey intent is simulated and cannot be substituted for actual sales or monthly demand.',
                    'Freight, discounts, taxes and other extras require manual validation; no landed cost is calculated.',
                    'Operating expenses, starting cash, growth and purchase quantity remain explicit scenario inputs. No complete business forecast is qualified here.',
                    'Historical prices are not verified current offers. Review source dates, terms and comparable product differences before use.',
                ],
            ],
        ];
    }

    private function check(string $state, string $reason): array
    {
        return compact('state', 'reason');
    }

    private function row(EvidenceRecord $record, bool $exact): array
    {
        $source = (new ReconciliationEvidenceResource($record))->resolve(request());
        $context = $source['source_context'];
        $supplier = $record->supplierObservation;
        $market = $record->marketObservation;
        $category = $supplier || $record->source_type === 'supplier' ? 'supplier'
            : ($market || in_array($record->source_type, ['market', 'competitor', 'government', 'government_statistics'], true) ? 'market'
                : ($record->simulatedPurchaseEvent ? 'simulation' : 'consumer'));
        $commercial = [];
        foreach (['purchase_price', 'currency_code', 'minimum_order_quantity', 'available_quantity', 'product_specification', 'freight_amount', 'freight_terms', 'payment_terms', 'lead_time_days', 'discount_amount', 'discount_description', 'supplier_location'] as $field) {
            $commercial[$field] = $supplier?->{$field};
        }
        $unit = $supplier?->inventoryObjectUnit ?? $market?->inventoryObjectUnit;
        $amount = $supplier?->purchase_price;
        $currency = $supplier?->currency_code;
        $unitCode = $supplier?->inventoryObjectUnit?->unit?->code;
        $basis = 'supplier_quote_per_selected_unit';
        if ($category === 'market') {
            // Imported selling_price may be a package total. Never qualify it as per-KG.
            $normalized = $context['normalized_price'] !== null;
            $amount = $normalized ? $context['normalized_price'] : ($record->import_key === null && blank($market?->package_size) ? $market?->selling_price : null);
            $currency = $normalized ? $context['normalized_currency'] : $market?->currency_code;
            $unitCode = $normalized ? $context['normalized_uom'] : $market?->inventoryObjectUnit?->unit?->code;
            $basis = $normalized ? 'source_supplied_normalized_unit_price' : 'market_price_per_selected_unit';
        }
        $qualified = in_array($record->epistemic_status, self::PRICE_STATUSES, true) && is_numeric($amount) && (float) $amount >= 0
            && is_string($currency) && filled($currency) && is_string($unitCode) && filled($unitCode);

        return [
            'id' => $record->id, 'category' => $category, 'group' => $exact ? 'exact' : 'comparable',
            'relationship' => $record->inventory_object_id !== null ? 'direct' : $record->currentReconciliation?->relationship_type,
            'epistemic_status' => $record->epistemic_status,
            'source_name' => $supplier?->supplier?->name ?? $market?->store_name ?? $context['source_name'],
            'commercial' => $commercial, 'purchase_unit' => $supplier?->inventoryObjectUnit?->unit?->code,
            'market' => ['description' => $context['product_description_raw'], 'channel' => $market?->channel ?? data_get($record->raw_payload, 'channel'),
                'listed_price' => $context['listed_price'] ?? $market?->selling_price,
                'currency' => $context['currency'] ?? $market?->currency_code,
                'package' => $market?->package_size, 'original_quantity' => $context['original_quantity'], 'original_uom' => $context['original_uom'],
                'selected_uom' => $market?->inventoryObjectUnit?->unit?->code,
                'normalized_price' => $context['normalized_price'], 'normalized_currency' => $context['normalized_currency'], 'normalized_uom' => $context['normalized_uom'],
                'promotion' => $market?->promotion ?? data_get($record->raw_payload, 'promotion')],
            'qualified_price' => $qualified ? ['value' => (string) $amount, 'currency' => strtoupper($currency), 'unit_code' => strtoupper($unitCode),
                'inventory_object_unit_id' => $unit?->id, 'amount_basis' => $basis,
                'source' => $record->source_type, 'epistemic_status' => $record->epistemic_status,
                'evidence_reference' => 'evidence_records:'.$record->id, 'assumption_reference' => null,
                'observed_at' => $source['observed_at']] : null,
            'provenance' => $source,
        ];
    }
}
