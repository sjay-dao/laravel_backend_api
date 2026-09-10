<?php

namespace Tests\Feature;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Services\EvidenceReconciliationService;
use App\Domains\Evidence\Services\EvidenceService;
use App\Domains\Evidence\Services\SurveyService;
use App\Domains\System\Models\User;
use Database\Seeders\EvidencePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsEvidenceTestDatabase;
use Tests\TestCase;

class ProductEvidenceSummaryTest extends TestCase
{
    use BuildsEvidenceTestDatabase;

    private function setupProduct(): array
    {
        $ids = $this->existingEntities();
        DB::table('inventory_objects')->where('id', $ids[2])->update(['code' => 'COCO-01', 'name' => 'Cocoa powder', 'brand' => 'Cacao', 'packaging_description' => 'Bulk']);
        $this->seed(EvidencePermissionSeeder::class);
        $role = DB::table('roles')->insertGetId(['code' => 'summary-reader', 'name' => 'Summary reader', 'is_active' => true]);
        DB::table('user_roles')->insert(['user_id' => $ids[0], 'role_id' => $role]);
        DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => DB::table('permissions')->where('code', 'evidence.products.view')->value('id')]);
        Sanctum::actingAs(User::findOrFail($ids[0]));

        return $ids;
    }

    private function read(int $product)
    {
        return $this->getJson('/api/evidence/inventory-objects/'.$product.'/evidence')->assertOk();
    }

    private function quote(array $ids, array $extra = [])
    {
        return app(EvidenceService::class)->recordSupplierObservation(array_replace([
            'supplier_id' => $ids[1], 'inventory_object_unit_id' => $ids[3], 'purchase_price' => 47,
            'collection_method' => 'email', 'source_reference' => 'QUOTE-1', 'observed_at' => '2026-09-09 08:00:00',
        ], $extra), $ids[0]);
    }

    private function market(array $ids, array $extra = [])
    {
        return app(EvidenceService::class)->recordMarketObservation(array_replace([
            'inventory_object_unit_id' => $ids[3], 'store_name' => 'Cocoa Market', 'channel' => 'retail', 'selling_price' => 65,
            'collection_method' => 'direct_observation', 'observed_at' => '2026-09-09 08:00:00',
        ], $extra), $ids[0]);
    }

    private function external(array $ids): EvidenceRecord
    {
        return EvidenceRecord::create(['source_type' => 'market', 'epistemic_status' => 'observed', 'recorded_at' => now(),
            'raw_payload' => ['source_name' => 'Other Cocoa', 'listed_price_php' => 600, 'package_qty' => 10, 'package_uom' => 'KG'],
            'context_payload' => ['normalized' => ['unit_amount' => 60, 'currency_code' => 'PHP', 'unit_code' => 'KG'],
                'product_reconciliation' => ['candidate_inventory_objects' => [['id' => $ids[2]]]]]]);
    }

    public function test_non_rice_product_with_no_evidence_preserves_missing_values_and_has_no_demand_estimate(): void
    {
        $ids = $this->setupProduct();
        $this->read($ids[2])->assertJsonPath('data.product.code', 'COCO-01')
            ->assertJsonPath('data.summary.readiness.status', 'NOT_READY')
            ->assertJsonPath('data.summary.consumer.state', 'NOT_COLLECTED')
            ->assertJsonPath('data.summary.consumer.demand_estimate', null)
            ->assertJsonPath('data.summary.consumer.raw_response_count', 0)
            ->assertJsonPath('data.summary.missing_evidence.acquisition_price.state', 'MISSING')
            ->assertJsonPath('data.summary.missing_evidence.freight_extras.state', 'MISSING')
            ->assertJsonCount(0, 'data.summary.supplier');
    }

    public function test_exact_comparable_unresolved_and_rejected_are_separate_including_revisions(): void
    {
        $ids = $this->setupProduct();
        $exact = $this->external($ids);
        $comparable = $this->external($ids);
        $unresolved = $this->external($ids);
        $rejected = $this->external($ids);
        $service = app(EvidenceReconciliationService::class);
        foreach ([[$exact, 'exact'], [$comparable, 'category_comparable'], [$rejected, 'rejected']] as [$record, $relationship]) {
            $service->reconcile($record, ['relationship_type' => $relationship, 'inventory_object_id' => $relationship === 'rejected' ? null : $ids[2], 'notes' => 'Human fixture review'], $ids[0]);
        }
        $before = $unresolved->fresh()->getRawOriginal();
        $this->read($ids[2])->assertJsonCount(1, 'data.summary.market.exact')->assertJsonCount(1, 'data.summary.market.comparable')
            ->assertJsonPath('data.summary.relationships.unresolved', 1)
            ->assertJsonPath('data.summary.market.comparable.0.relationship', 'category_comparable')
            ->assertJsonPath('data.summary.market.exact.0.market.listed_price', 600)
            ->assertJsonPath('data.summary.market.exact.0.market.normalized_price', 60)
            ->assertJsonPath('data.summary.readiness.status', 'PARTIALLY_READY');
        $this->assertSame($before, $unresolved->fresh()->getRawOriginal());
        $service->reconcile($exact, ['relationship_type' => 'unresolved', 'notes' => 'Identity needs more work'], $ids[0]);
        $this->read($ids[2])->assertJsonCount(0, 'data.summary.market.exact')->assertJsonPath('data.summary.relationships.unresolved', 2);
        $service->reconcile($comparable, ['relationship_type' => 'variant_match', 'inventory_object_id' => $ids[2], 'notes' => 'Variant only'], $ids[0]);
        $this->read($ids[2])->assertJsonPath('data.summary.market.comparable.0.relationship', 'variant_match');
    }

    public function test_readiness_is_deterministic_quotes_are_reported_and_unknown_freight_is_not_zero(): void
    {
        $ids = $this->setupProduct();
        $this->quote($ids);
        $this->market($ids);
        $first = $this->read($ids[2])->assertJsonPath('data.summary.readiness.status', 'READY_FOR_MANUAL_SCENARIO')
            ->assertJsonPath('data.summary.readiness.forecast_status', 'PARTIALLY_READY')
            ->assertJsonPath('data.summary.supplier.0.commercial.freight_amount', null)
            ->assertJsonPath('data.summary.supplier.0.commercial.minimum_order_quantity', null)
            ->assertJsonPath('data.summary.supplier.0.qualified_price.epistemic_status', 'reported')
            ->assertJsonPath('data.summary.supplier.0.qualified_price.value', '47.000000')
            ->assertJsonPath('data.summary.consumer.demand_estimate', null)->json('data.summary');
        $this->assertSame($first, $this->read($ids[2])->json('data.summary'));
        $this->assertStringContainsString('not profitability', $first['readiness']['scope']);
        $this->assertStringContainsString('Demand is unsupported', $first['readiness']['warnings'][0]);
        $this->assertArrayNotHasKey('profit', $first);
        $this->assertArrayNotHasKey('recommended_price', $first);
    }

    public function test_zero_quote_and_zero_freight_remain_explicit_known_values(): void
    {
        $ids = $this->setupProduct();
        $this->quote($ids, ['purchase_price' => 0, 'freight_amount' => 0, 'lead_time_days' => 0]);
        $this->read($ids[2])->assertJsonPath('data.summary.supplier.0.commercial.purchase_price', '0.000000')
            ->assertJsonPath('data.summary.supplier.0.commercial.freight_amount', '0.000000')
            ->assertJsonPath('data.summary.supplier.0.commercial.lead_time_days', 0)
            ->assertJsonPath('data.summary.missing_evidence.freight_extras.state', 'PARTIAL');
    }

    public function test_assumptions_remain_visible_but_do_not_qualify_as_supplier_cost(): void
    {
        $ids = $this->setupProduct();
        foreach (EvidenceService::EPISTEMIC_STATUSES as $status) {
            $this->market($ids, ['epistemic_status' => $status]);
        }
        $this->quote($ids, ['epistemic_status' => 'assumed']);
        $summary = $this->read($ids[2])->assertJsonPath('data.summary.supplier.0.qualified_price', null)
            ->assertJsonPath('data.summary.missing_evidence.acquisition_price.state', 'PARTIAL')
            ->assertJsonPath('data.summary.readiness.status', 'PARTIALLY_READY')->json('data.summary');
        foreach (EvidenceService::EPISTEMIC_STATUSES as $status) {
            $this->assertSame($status === 'assumed' ? 2 : 1, $summary['epistemic_breakdown'][$status]['exact']);
        }
    }

    public function test_package_totals_and_mismatched_currency_do_not_make_scenario_ready(): void
    {
        $ids = $this->setupProduct();
        $this->quote($ids);
        $this->market($ids, ['package_size' => '25 KG', 'selling_price' => 1500]);
        $this->read($ids[2])->assertJsonPath('data.summary.market.exact.0.qualified_price', null)
            ->assertJsonPath('data.summary.readiness.status', 'PARTIALLY_READY');
        $this->market($ids, ['currency_code' => 'USD']);
        $this->read($ids[2])->assertJsonPath('data.summary.missing_evidence.scenario_ready_uom.state', 'PARTIAL');
        DB::table('inventory_object_units')->where('id', $ids[3])->update(['conversion_factor' => null]);
        $this->read($ids[2])->assertJsonPath('data.summary.missing_evidence.quantity_uom.state', 'MISSING');
    }

    public function test_comparable_price_context_can_support_manual_scenario_but_not_replace_exact_identity(): void
    {
        $ids = $this->setupProduct();
        $this->quote($ids);
        $record = $this->external($ids);
        app(EvidenceReconciliationService::class)->reconcile($record, ['relationship_type' => 'category_comparable', 'inventory_object_id' => $ids[2], 'notes' => 'Context only'], $ids[0]);
        $this->read($ids[2])->assertJsonCount(0, 'data.summary.market.exact')->assertJsonCount(1, 'data.summary.market.comparable')
            ->assertJsonPath('data.summary.readiness.status', 'READY_FOR_MANUAL_SCENARIO')
            ->assertJsonPath('data.summary.market.comparable.0.qualified_price.evidence_reference', 'evidence_records:'.$record->id);
    }

    public function test_consumer_responses_and_existing_simulated_projection_keep_their_semantics(): void
    {
        $ids = $this->setupProduct();
        $surveys = app(SurveyService::class);
        $survey = $surveys->create(['code' => 'COCOA-INTENT', 'name' => 'Cocoa research', 'questions' => [[
            'question_key' => 'reason', 'prompt' => 'Why?', 'question_type' => 'text', 'is_required' => false,
        ]], 'scenarios' => [['scenario_code' => 'COCOA-65', 'inventory_object_id' => $ids[2], 'inventory_object_unit_id' => $ids[3], 'proposed_price' => 65]]], $ids[0]);
        $survey = $surveys->publish($survey, $ids[0]);
        $this->read($ids[2])->assertJsonPath('data.summary.consumer.configured_scenario_count', 1)
            ->assertJsonPath('data.summary.consumer.tested_scenario_count', 0)->assertJsonPath('data.summary.consumer.state', 'NOT_COLLECTED');
        app(EvidenceService::class)->submitResponse(['survey_id' => $survey->id, 'respondent_code' => 'C1', 'raw_payload' => ['intent' => 'No'], 'answers' => [],
            'simulated_purchase' => ['survey_scenario_id' => $survey->scenarios->first()->id, 'would_purchase' => false, 'quantity' => 0]]);
        $this->read($ids[2])->assertJsonPath('data.summary.consumer.state', 'AVAILABLE')
            ->assertJsonPath('data.summary.consumer.raw_response_count', 1)->assertJsonPath('data.summary.consumer.respondent_count', 1)
            ->assertJsonPath('data.summary.consumer.tested_scenario_count', 1)->assertJsonPath('data.summary.consumer.demand_estimate', null)
            ->assertJsonPath('data.summary.simulated_events.0.event_type', 'consumer_purchase_intent')
            ->assertJsonPath('data.summary.simulated_events.0.epistemic_status', 'simulated')
            ->assertJsonPath('data.summary.simulated_events.0.total_amount', null)
            ->assertJsonPath('data.summary.simulated_events.0.currency', 'PHP')
            ->assertJsonPath('data.consumer_evidence.0.raw_payload.intent', 'No');
    }

    public function test_summary_and_inventory_lookup_are_read_only_and_product_permission_is_sufficient(): void
    {
        $ids = $this->setupProduct();
        $this->quote($ids);
        $tables = ['sales_orders', 'orders', 'inventory_movements', ...$this->additionalOperationalTables()];
        foreach ($tables as $table) {
            DB::table($table)->insert(['id' => 42]);
        }
        $tables = [...$tables, 'inventory_objects', 'inventory_object_units', 'suppliers', 'evidence_records', 'supplier_product_observations'];
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toJson()]);
        $this->read($ids[2]);
        $this->getJson('/api/evidence/inventory-objects?search=COCO-01')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ids[2]);
        $this->getJson('/api/evidence/inventory-objects?search=missing')->assertOk()->assertJsonCount(0, 'data');
        foreach ($tables as $table) {
            $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table);
        }
        for ($i = 0; $i < 23; $i++) {
            DB::table('inventory_objects')->insert(['code' => 'TEST-'.$i, 'name' => 'Test '.$i, 'unit_id' => DB::table('units')->value('id')]);
        }
        $this->getJson('/api/evidence/inventory-objects?page=2&per_page=20')->assertOk()->assertJsonCount(4, 'data')->assertJsonPath('meta.total', 24);
        $this->getJson('/api/evidence/inventory-objects?per_page=101')->assertUnprocessable();
        DB::table('role_permissions')->delete();
        Sanctum::actingAs(User::findOrFail($ids[0]));
        $this->getJson('/api/evidence/inventory-objects')->assertForbidden();
        $this->getJson('/api/evidence/inventory-objects/'.$ids[2].'/evidence')->assertForbidden();
    }

    public function test_external_supplier_details_survive_review_but_untyped_terms_are_not_qualified_quotes(): void
    {
        $ids = $this->setupProduct();
        $record = EvidenceRecord::create(['source_type' => 'supplier', 'epistemic_status' => 'reported', 'recorded_at' => now(),
            'raw_payload' => ['source_name' => 'Supplier lead', 'message' => 'Ask for quotation', 'payment_terms' => 'Discuss on call']]);
        app(EvidenceReconciliationService::class)->reconcile($record, ['relationship_type' => 'exact', 'inventory_object_id' => $ids[2], 'notes' => 'Product identity verified, quote still needed'], $ids[0]);
        $this->read($ids[2])->assertJsonPath('data.summary.supplier.0.source_name', 'Supplier lead')
            ->assertJsonPath('data.summary.supplier.0.provenance.raw_payload.payment_terms', 'Discuss on call')
            ->assertJsonPath('data.summary.supplier.0.qualified_price', null)
            ->assertJsonPath('data.summary.missing_evidence.acquisition_price.state', 'PARTIAL');
    }

    public function test_incomplete_normalization_and_incompatible_units_remain_partial(): void
    {
        $ids = $this->setupProduct();
        $this->quote($ids);
        $record = $this->external($ids);
        app(EvidenceReconciliationService::class)->reconcile($record, ['relationship_type' => 'exact', 'inventory_object_id' => $ids[2], 'notes' => 'Identity verified'], $ids[0]);
        // Fixture represents different source formats; summary must not infer missing currency or convert BOX to KG.
        foreach ([['unit_amount' => 60, 'unit_code' => 'KG'], ['unit_amount' => 60, 'currency_code' => 'PHP', 'unit_code' => 'BOX'], ['unit_amount' => 60, 'currency_code' => ['unknown'], 'unit_code' => 'KG']] as $normalization) {
            $record->update(['context_payload' => ['normalized' => $normalization]]);
            $this->read($ids[2])->assertJsonPath('data.summary.readiness.status', 'PARTIALLY_READY');
        }
    }

    public function test_anonymous_users_cannot_read_product_summary_or_product_lookup(): void
    {
        [, , $product] = $this->existingEntities();
        $this->getJson('/api/evidence/inventory-objects')->assertUnauthorized();
        $this->getJson('/api/evidence/inventory-objects/'.$product.'/evidence')->assertUnauthorized();
    }
}
