<?php

namespace Tests\Feature;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Models\SimulatedPurchaseEvent;
use App\Domains\Evidence\Models\Survey;
use App\Domains\Evidence\Models\SurveyRespondent;
use App\Domains\Evidence\Models\SurveyResponseAnswer;
use App\Domains\Evidence\Services\EvidenceReconciliationService;
use App\Domains\Evidence\Services\EvidenceScenarioService;
use App\Domains\Evidence\Services\EvidenceService;
use App\Domains\Evidence\Services\SimulatedPurchaseProjection;
use App\Domains\Evidence\Services\SurveyService;
use App\Domains\System\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsEvidenceTestDatabase;
use Tests\TestCase;

class EvidenceAcquisitionTest extends TestCase
{
    use BuildsEvidenceTestDatabase;

    public function test_evidence_is_historical_provenanced_and_separate_from_actual_sales(): void
    {
        [$userId, $supplierId, $productId, $productUnitId] = $this->existingEntities();
        $evidence = app(EvidenceService::class);

        $firstSupplierObservation = $evidence->recordSupplierObservation([
            'supplier_id' => $supplierId,
            'inventory_object_unit_id' => $productUnitId,
            'purchase_price' => 50,
            'minimum_order_quantity' => 100,
            'available_quantity' => 2000,
            'collection_method' => 'phone',
            'source_reference' => 'CALL-001',
            'observed_at' => '2026-09-08 09:00:00',
            'raw_payload' => ['quoted_price_text' => 'PHP 50 per kg'],
        ], $userId);

        $secondSupplierObservation = $evidence->recordSupplierObservation([
            'supplier_id' => $supplierId,
            'inventory_object_unit_id' => $productUnitId,
            'purchase_price' => 53,
            'collection_method' => 'phone',
            'source_reference' => 'CALL-002',
            'observed_at' => '2026-09-08 11:00:00',
            'raw_payload' => ['quoted_price_text' => 'PHP 53 per kg'],
        ], $userId);

        $marketObservation = $evidence->recordMarketObservation([
            'inventory_object_unit_id' => $productUnitId,
            'store_name' => 'Rice Market A',
            'channel' => 'retail',
            'selling_price' => 325,
            'package_size' => '5 kg',
            'availability_status' => 'available',
            'collection_method' => 'direct_observation',
            'source_reference' => 'VISIT-001',
            'location_name' => 'Quezon City',
            'observed_at' => '2026-09-08 12:00:00',
            'raw_payload' => ['shelf_label' => 'Rice 5 kg PHP 325'],
        ], $userId);

        $assumedMarketObservation = $evidence->recordMarketObservation([
            'inventory_object_unit_id' => $productUnitId,
            'store_name' => 'Planning assumption',
            'channel' => 'retail',
            'selling_price' => 330,
            'collection_method' => 'planning_session',
            'observed_at' => '2026-09-08 13:00:00',
            'epistemic_status' => 'assumed',
        ], $userId);

        $this->assertNotSame($firstSupplierObservation->id, $secondSupplierObservation->id);
        $this->assertDatabaseCount('supplier_product_observations', 2);
        $this->assertSame('reported', $firstSupplierObservation->evidence()->value('epistemic_status'));
        $this->assertSame('supplier', $secondSupplierObservation->evidence()->value('source_type'));
        $this->assertSame('CALL-001', $firstSupplierObservation->evidence()->value('source_reference'));
        $this->assertSame('observed', $marketObservation->evidence()->value('epistemic_status'));
        $this->assertSame('assumed', $assumedMarketObservation->evidence()->value('epistemic_status'));

        $surveys = app(SurveyService::class);
        $survey = $surveys->create([
            'code' => 'RICE-CONSUMER-001',
            'name' => 'Rice consumer behavior',
            'questions' => [
                [
                    'question_key' => 'current_package_size',
                    'prompt' => 'Which rice package do you currently buy?',
                    'question_type' => 'single_choice',
                    'is_required' => true,
                    'options' => [
                        [
                            'option_code' => 'five_kg',
                            'label' => '5 kg',
                            'raw_value' => '5kg',
                        ],
                    ],
                ],
                [
                    'question_key' => 'purchase_frequency',
                    'prompt' => 'How often do you buy rice?',
                    'question_type' => 'text',
                    'is_required' => true,
                ],
            ],
            'scenarios' => [[
                'scenario_code' => 'RICE-5KG-325',
                'inventory_object_id' => $productId,
                'inventory_object_unit_id' => $productUnitId,
                'proposed_price' => 325,
                'description' => '5 kg rice purchasing scenario',
            ]],
        ], $userId);

        $survey = $surveys->publish($survey, $userId);
        $packageQuestion = $survey->questions->firstWhere('question_key', 'current_package_size');
        $frequencyQuestion = $survey->questions->firstWhere('question_key', 'purchase_frequency');
        $packageOption = $packageQuestion->options->first();
        $scenario = $survey->scenarios->first();

        $response = $evidence->submitResponse([
            'survey_id' => $survey->id,
            'respondent_code' => 'R027',
            'session_reference' => 'rice-test-session-027',
            'profile_payload' => ['location' => 'Quezon City'],
            'raw_payload' => [
                'current_behavior' => ['current_price' => 300, 'channel' => 'wet market'],
            ],
            'answers' => [
                [
                    'survey_question_id' => $packageQuestion->id,
                    'survey_question_option_id' => $packageOption->id,
                    'raw_value' => ['selected' => '5 kg'],
                ],
                [
                    'survey_question_id' => $frequencyQuestion->id,
                    'raw_text' => 'Every two weeks',
                ],
            ],
            'simulated_purchase' => [
                'survey_scenario_id' => $scenario->id,
                'would_purchase' => true,
                'quantity' => 1,
                'frequency_raw' => 'Every two weeks',
                'estimated_frequency_per_month' => 2,
                'decision_reason' => 'Price is acceptable.',
                'derived_payload' => ['frequency_method' => 'manual normalization'],
            ],
        ]);

        $rawAnswer = SurveyResponseAnswer::query()
            ->where('survey_response_id', $response->id)
            ->where('survey_question_id', $packageQuestion->id)
            ->firstOrFail();
        $simulatedEvent = SimulatedPurchaseEvent::query()->firstOrFail();
        $simulatedEvidence = EvidenceRecord::query()->findOrFail(
            $simulatedEvent->evidence_record_id
        );

        $this->assertSame(['selected' => '5 kg'], $rawAnswer->raw_value);
        $this->assertSame('consumer_survey', $simulatedEvent->source_type);
        $this->assertSame('simulated', $simulatedEvent->epistemic_status);
        $this->assertSame('consumer_survey', $simulatedEvidence->source_type);
        $this->assertSame('simulated', $simulatedEvidence->epistemic_status);
        $this->assertSame(1.0, (float) $simulatedEvent->simulated_quantity);
        $this->assertSame(1.0, (float) $simulatedEvent->base_quantity);
        $this->assertSame(0, DB::table('sales_orders')->count());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('inventory_movements')->count());

        $timeline = $evidence->forInventoryObject($productId);

        $this->assertCount(5, $timeline);
        $this->assertSame(2, $timeline->where('source_type', 'supplier')->count());
        $this->assertSame(2, $timeline->where('source_type', 'market')->count());
        $this->assertSame(1, $timeline->where('source_type', 'consumer_survey')->count());
        $consumerRecord = $timeline->firstWhere('source_type', 'consumer_survey');
        $this->assertNotNull($consumerRecord);
        $this->assertCount(
            2,
            $consumerRecord->simulatedPurchaseEvent->response->answers
        );
    }

    public function test_public_survey_endpoint_is_available_without_auth_but_internal_evidence_is_not(): void
    {
        [$userId, , $productId, $productUnitId] = $this->existingEntities();
        $survey = app(SurveyService::class)->create([
            'code' => 'RICE-PUBLIC-001',
            'name' => 'Public Rice Survey',
            'questions' => [[
                'question_key' => 'current_brand',
                'prompt' => 'What rice brand do you buy?',
                'question_type' => 'text',
                'is_required' => true,
            ]],
            'scenarios' => [[
                'scenario_code' => 'PUBLIC-RICE-5KG',
                'inventory_object_id' => $productId,
                'inventory_object_unit_id' => $productUnitId,
                'proposed_price' => 325,
            ]],
        ], $userId);

        $this->getJson("/api/evidence/public/surveys/{$survey->code}")
            ->assertNotFound();

        $survey = app(SurveyService::class)->publish($survey, $userId);
        $question = $survey->questions->first();
        $scenario = $survey->scenarios->first();

        $this->getJson("/api/evidence/public/surveys/{$survey->code}")
            ->assertOk()
            ->assertJsonPath('data.code', $survey->code)
            ->assertJsonPath('data.scenarios.0.scenario_code', $scenario->scenario_code);

        $this->postJson("/api/evidence/public/surveys/{$survey->code}/responses", [
            'respondent_code' => 'PUBLIC-001',
            'answers' => [[
                'survey_question_id' => $question->id,
                'raw_text' => 'Sample Rice',
            ]],
            'simulated_purchase' => [
                'survey_scenario_id' => $scenario->id,
                'would_purchase' => true,
                'quantity' => 1,
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.simulated_purchase_events.0.source_type', 'consumer_survey')
            ->assertJsonPath('data.simulated_purchase_events.0.epistemic_status', 'simulated');

        $this->getJson('/api/evidence/supplier-observations')
            ->assertUnauthorized();
    }

    public function test_internal_evidence_api_requires_and_honors_the_existing_permission_system(): void
    {
        [$userId, $supplierId, $productId, $productUnitId] = $this->existingEntities();
        $user = User::query()->findOrFail($userId);

        Sanctum::actingAs($user, ['*']);

        $payload = [
            'supplier_id' => $supplierId,
            'inventory_object_unit_id' => $productUnitId,
            'purchase_price' => 52,
            'collection_method' => 'phone',
            'observed_at' => '2026-09-08 10:00:00',
        ];

        $this->postJson('/api/evidence/supplier-observations', $payload)
            ->assertForbidden();

        $roleId = DB::table('roles')->insertGetId([
            'code' => 'evidence_collector',
            'name' => 'Evidence Collector',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = DB::table('permissions')->insertGetId([
            'module' => 'evidence',
            'resource' => 'supplier_observations',
            'action' => 'create',
            'code' => 'evidence.supplier_observations.create',
            'description' => 'Create supplier observations',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
        DB::table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);

        $this->postJson('/api/evidence/supplier-observations', $payload)
            ->assertCreated()
            ->assertJsonPath('data.provenance.source_type', 'supplier')
            ->assertJsonPath('data.provenance.epistemic_status', 'reported');

        $viewPermissionId = DB::table('permissions')->insertGetId([
            'module' => 'evidence',
            'resource' => 'products',
            'action' => 'view',
            'code' => 'evidence.products.view',
            'description' => 'View product evidence',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $viewPermissionId,
        ]);

        $this->getJson("/api/evidence/inventory-objects/{$productId}/evidence")
            ->assertOk()
            ->assertJsonCount(1, 'data.supplier_evidence')
            ->assertJsonPath('data.evidence_records.0.source_type', 'supplier');
    }

    public function test_scenarios_project_separately_and_never_write_operational_tables(): void
    {
        [$user, , $product, $uom] = $this->existingEntities();
        // A non-Rice product follows exactly the same acquisition path.
        DB::table('inventory_objects')->where('id', $product)->update(['name' => 'Cooking oil']);
        $a = $this->purchaseSurvey('OIL-A', $user, $product, $uom, 'USD');
        $box = DB::table('units')->insertGetId(['code' => 'BOX', 'name' => 'Box']);
        $boxUom = DB::table('inventory_object_units')->insertGetId([
            'inventory_object_id' => $product, 'unit_id' => $box, 'conversion_factor' => 25,
        ]);
        $b = $this->purchaseSurvey('OIL-B', $user, $product, $boxUom, 'PHP');
        $tables = ['sales_orders', 'orders', 'inventory_movements', ...$this->additionalOperationalTables()];
        foreach ($tables as $table) {
            DB::table($table)->insert(['id' => 42, 'created_at' => now(), 'updated_at' => now()]);
        }
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toJson()]);
        $first = app(EvidenceService::class)->submitResponse($this->purchaseResponse($a));
        $second = app(EvidenceService::class)->submitResponse($this->purchaseResponse($b));
        $this->assertSame('50.000000', $second->simulatedPurchaseEvents->sole()->base_quantity);
        $this->assertSame('BOX', $second->simulatedPurchaseEvents->sole()->unit_snapshot['unit_code']);
        $event = $first->simulatedPurchaseEvents->sole();
        $projection = $event->toBusinessEventProjection();
        $this->assertSame('consumer_purchase_intent', $projection->event_type);
        $this->assertSame('USD', $projection->currency);
        $this->assertSame('simulated', $projection->epistemic_status);
        $this->assertSame('consumer_survey', $projection->source);
        $this->assertSame('survey_respondent', $projection->counterparty['type']);
        $this->assertSame('2.000000', $projection->quantity);
        $this->assertNull($projection->total_amount);
        $this->assertSame($first->id, $projection->provenance['survey_response_id']);
        $scenario = $a->scenarios->sole()->evidenceScenario;
        $this->assertSame('evidence_scenarios:'.$scenario->id, $projection->scenario);
        $this->assertCount(1, app(SimulatedPurchaseProjection::class)->forScenario($scenario));
        foreach ($tables as $table) {
            $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table.' was changed');
        }
        // Projections retain captured UOM and currency after master edits.
        DB::table('units')->update(['name' => 'Renamed unit']);
        DB::table('inventory_object_units')->update(['conversion_factor' => 50]);
        $later = $event->fresh()->toBusinessEventProjection();
        $this->assertSame('Kilogram', $later->unit['snapshot']['unit_name']);
        $this->assertSame('1.000000', $later->unit['conversion_factor']);
        $this->assertSame('USD', $later->currency);
    }

    public function test_unknown_conversion_and_commercial_values_are_not_zero(): void
    {
        [$user, $supplier, $product, $uom] = $this->existingEntities();
        $survey = $this->purchaseSurvey('UNKNOWN', $user, $product, $uom);
        DB::table('inventory_object_units')->where('id', $uom)->update(['conversion_factor' => null]);
        $response = app(EvidenceService::class)->submitResponse($this->purchaseResponse($survey));
        $event = $response->simulatedPurchaseEvents->sole();
        $this->assertSame('2.000000', $event->simulated_quantity);
        $this->assertNull($event->base_quantity);
        $this->assertNull($event->conversion_factor);
        $observation = app(EvidenceService::class)->recordSupplierObservation([
            'supplier_id' => $supplier, 'inventory_object_unit_id' => $uom,
            'purchase_price' => 12, 'collection_method' => 'phone', 'observed_at' => now(),
            'context_payload' => ['value_states' => ['freight_amount' => 'not_collected']],
        ], $user);
        foreach (['freight_amount', 'discount_amount', 'lead_time_days', 'minimum_order_quantity'] as $field) {
            $this->assertNull($observation->$field);
        }
        $this->assertSame('not_collected', $observation->evidence->context_payload['value_states']['freight_amount']);
    }

    public function test_conflicting_units_currency_inactive_and_cross_survey_scenarios_are_rejected(): void
    {
        [$user, , $product, $uom] = $this->existingEntities();
        $survey = $this->purchaseSurvey('MATCH', $user, $product, $uom);
        $other = $this->purchaseSurvey('OTHER', $user, $product, $uom);
        $box = DB::table('units')->insertGetId(['code' => 'BOX', 'name' => 'Box']);
        $boxUom = DB::table('inventory_object_units')->insertGetId([
            'inventory_object_id' => $product, 'unit_id' => $box, 'conversion_factor' => 25,
        ]);
        foreach ([
            ['inventory_object_unit_id' => $boxUom],
            ['survey_scenario_id' => $other->scenarios->sole()->id],
            ['currency_code' => 'EUR'],
            ['proposed_price' => 99],
        ] as $invalid) {
            $data = $this->purchaseResponse($survey);
            $data['simulated_purchase'] = array_replace($data['simulated_purchase'], $invalid);
            try {
                app(EvidenceService::class)->submitResponse($data);
                $this->fail('Invalid scenario context was accepted.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $survey->scenarios->sole()->update(['is_active' => false]);
        try {
            app(EvidenceService::class)->submitResponse($this->purchaseResponse($survey));
            $this->fail('Inactive scenario was accepted.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
        $this->assertDatabaseCount('survey_responses', 0);
        $this->assertDatabaseCount('evidence_records', 0);
        $survey->scenarios->sole()->update(['is_active' => true]);
        DB::table('inventory_object_units')->where('id', $uom)->update(['conversion_factor' => 0]);
        $this->expectException(ValidationException::class);
        app(EvidenceService::class)->submitResponse($this->purchaseResponse($survey));
    }

    public function test_epistemic_status_and_raw_history_remain_distinct(): void
    {
        [$user, , , $uom] = $this->existingEntities();
        foreach (EvidenceService::EPISTEMIC_STATUSES as $status) {
            app(EvidenceService::class)->recordMarketObservation([
                'inventory_object_unit_id' => $uom, 'store_name' => 'External retailer',
                'channel' => 'retail', 'selling_price' => 61.12, 'collection_method' => 'store_visit',
                'epistemic_status' => $status, 'observed_at' => '2026-01-01',
                'raw_payload' => ['label' => '25kg package 1528', 'original_uom' => 'kg'],
                'context_payload' => ['normalized' => ['price_per_kg' => 61.12]],
            ], $user);
        }
        $this->assertSame(EvidenceService::EPISTEMIC_STATUSES, EvidenceRecord::orderBy('id')->pluck('epistemic_status')->all());
        $first = EvidenceRecord::firstOrFail();
        $this->assertSame('25kg package 1528', $first->raw_payload['label']);
        $this->assertSame('2026-01-01', $first->observed_at->toDateString());
        $this->assertSame(61.12, $first->context_payload['normalized']['price_per_kg']);
        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_comparable_reconciliation_does_not_become_exact_or_destroy_raw_evidence(): void
    {
        [$user, , $product, $uom] = $this->existingEntities();
        $record = EvidenceRecord::create([
            'source_type' => 'market', 'epistemic_status' => 'reported', 'recorded_at' => now(),
            'raw_payload' => ['product' => 'Different variant', 'unit' => '25 kg sack'],
        ]);
        $service = app(EvidenceReconciliationService::class);
        $first = $service->reconcile($record, [
            'relationship_type' => 'category_comparable', 'inventory_object_id' => $product,
            'inventory_object_unit_id' => $uom, 'notes' => 'Comparable category only.',
        ], $user);
        $this->assertNull($record->fresh()->inventory_object_id);
        $this->assertCount(0, app(EvidenceService::class)->forInventoryObject($product));
        $service->reconcile($record, ['relationship_type' => 'unresolved', 'notes' => 'Needs further review.'], $user);
        $this->assertSame('superseded', $first->fresh()->reconciliation_status);
        $this->assertSame('category_comparable', $first->fresh()->relationship_type);
        $this->assertSame(['product' => 'Different variant', 'unit' => '25 kg sack'], $record->fresh()->raw_payload);
    }

    public function test_public_submission_requires_published_stimulus_and_keeps_profiles_private(): void
    {
        [$user, , $product, $uom] = $this->existingEntities();
        $survey = $this->purchaseSurvey('PRIVATE', $user, $product, $uom);
        $data = $this->purchaseResponse($survey);
        $data['profile_payload'] = ['private_note' => 'private profile'];
        $url = '/api/evidence/public/surveys/'.$survey->code;
        $invalid = $data;
        unset($invalid['simulated_purchase']['survey_scenario_id']);
        $this->postJson($url.'/responses', $invalid)->assertUnprocessable();
        $this->postJson($url.'/responses', $data)->assertCreated()
            ->assertJsonMissingPath('data.respondent')
            ->assertJsonMissingPath('data.answers')
            ->assertJsonMissingPath('data.raw_payload')
            ->assertJsonMissingPath('data.simulated_purchase_events.0.provenance');
        $this->getJson($url)->assertOk()->assertJsonMissingPath('data.responses')
            ->assertJsonMissingPath('data.scenarios.0.evidence_scenario_id');
        $this->assertSame('private profile', SurveyRespondent::sole()->profile_payload['private_note']);
    }

    public function test_independent_scenario_and_additive_migration_preserve_legacy_events(): void
    {
        [$user, , $product, $uom] = $this->existingEntities();
        $standalone = app(EvidenceScenarioService::class)->create([
            'code' => 'PROCUREMENT-EXPERIMENT-A', 'name' => 'Hypothetical procurement',
            'context_payload' => ['freight_amount' => null, 'value_states' => ['freight_amount' => 'unknown']],
        ], $user);
        $this->assertCount(0, $standalone->surveyScenarios);
        $survey = $this->purchaseSurvey('LEGACY', $user, $product, $uom, 'USD');
        $event = app(EvidenceService::class)->submitResponse($this->purchaseResponse($survey))->simulatedPurchaseEvents->sole();
        $unscopedData = $this->purchaseResponse($survey);
        unset($unscopedData['simulated_purchase']['survey_scenario_id']);
        $unscopedData['simulated_purchase']['inventory_object_unit_id'] = $uom;
        $unscopedData['simulated_purchase']['proposed_price'] = 10;
        $unscoped = app(EvidenceService::class)->submitResponse($unscopedData)->simulatedPurchaseEvents->sole();
        $raw = $event->evidence->raw_payload;
        $migration = require database_path('migrations/2026_09_09_090000_add_evidence_simulation_foundation.php');
        $migration->down();
        $migration->up();
        $event = $event->fresh();
        $this->assertSame('USD', $event->currency_code);
        $this->assertNull($event->unit_snapshot); // historical labels are not guessed
        $this->assertSame($raw, $event->evidence->raw_payload);
        $this->assertNotNull($event->evidence->evidence_scenario_id);
        $this->assertSame($survey->scenarios->sole()->id, $event->survey_scenario_id);
        $this->assertNull($unscoped->fresh()->currency_code);
        $this->assertNull($unscoped->fresh()->evidence->evidence_scenario_id);
    }

    private function purchaseSurvey(string $code, int $user, int $product, int $uom, string $currency = 'PHP'): Survey
    {
        $service = app(SurveyService::class);

        return $service->publish($service->create([
            'code' => $code, 'name' => $code,
            'questions' => [['question_key' => 'current', 'prompt' => 'Current product?', 'question_type' => 'text']],
            'scenarios' => [[
                'scenario_code' => $code.'-A', 'inventory_object_id' => $product,
                'inventory_object_unit_id' => $uom, 'proposed_price' => 10, 'currency_code' => $currency,
            ]],
        ], $user), $user);
    }

    private function purchaseResponse(Survey $survey): array
    {
        return [
            'survey_id' => $survey->id,
            'answers' => [['survey_question_id' => $survey->questions->sole()->id, 'raw_text' => 'Original answer']],
            'simulated_purchase' => ['survey_scenario_id' => $survey->scenarios->sole()->id, 'would_purchase' => true, 'quantity' => 2],
        ];
    }
}
