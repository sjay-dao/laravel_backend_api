<?php

namespace Tests\Feature;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Services\RiceEvidenceImportService;
use App\Domains\System\Models\User;
use Database\Seeders\EvidencePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsEvidenceTestDatabase;
use Tests\TestCase;

class EvidenceReconciliationTest extends TestCase
{
    use BuildsEvidenceTestDatabase;

    public function test_imported_unmatched_evidence_is_visible_without_reimport_or_automatic_linking(): void
    {
        [$user, , $product] = $this->existingEntities();
        $record = $this->importedRecord();
        $before = $record->getRawOriginal();
        $this->signIn($user, ['evidence.reconciliations.view']);
        $this->getJson('/api/evidence/reconciliations')->assertOk()
            ->assertJsonPath('data.0.id', $record->id)
            ->assertJsonPath('data.0.inventory_object_id', null)
            ->assertJsonPath('data.0.current_reconciliation', null)
            ->assertJsonPath('data.0.source_context.original_uom', 'kg')
            ->assertJsonPath('data.0.source_context.normalized_price', 61.12);
        $this->getJson($this->url($record).'/candidates?search=RICE-A')->assertOk()
            ->assertJsonPath('data.candidates.0.id', $product);
        $this->getJson($this->url($record).'/candidates?search=does-not-exist')->assertOk()
            ->assertJsonCount(0, 'data.candidates');
        $this->assertSame($before, $record->fresh()->getRawOriginal());
        $this->assertDatabaseCount('evidence_reconciliations', 0);
        $this->assertDatabaseCount('inventory_objects', 1);
        $this->assertDatabaseCount('suppliers', 1);
    }

    public function test_exact_review_preserves_every_source_field_and_operational_state(): void
    {
        [$user, , $product, $uom] = $this->existingEntities();
        $record = $this->importedRecord();
        $sourceBefore = $record->getRawOriginal();
        $this->signIn($user, ['evidence.reconciliations.view', 'evidence.reconciliations.create', 'evidence.products.view']);
        $tables = ['sales_orders', 'orders', 'inventory_movements', ...$this->additionalOperationalTables()];
        foreach ($tables as $table) {
            DB::table($table)->insert(['id' => 42]);
        }
        $tables = [...$tables, 'inventory_objects', 'inventory_object_units', 'suppliers'];
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toJson()]);
        $result = $this->postJson($this->url($record), [
            'relationship_type' => 'exact', 'inventory_object_id' => $product,
            'inventory_object_unit_id' => $uom, 'notes' => 'Verified product label and packaging.',
        ])->assertCreated()->assertJsonPath('data.relationship_type', 'exact')
            ->assertJsonPath('data.reconciliation_status', 'active')
            ->assertJsonPath('data.reconciler.id', $user);
        $this->assertNotEmpty($result->json('data.reconciled_at'));
        $this->assertSame($sourceBefore, $record->fresh()->getRawOriginal());
        foreach ($tables as $table) {
            $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table);
        }
        $this->getJson('/api/evidence/reconciliations')->assertJsonCount(0, 'data');
        $this->getJson("/api/evidence/inventory-objects/{$product}/evidence")->assertOk()
            ->assertJsonCount(1, 'data.exact_evidence')->assertJsonCount(0, 'data.comparable_evidence')
            ->assertJsonPath('data.exact_evidence.0.id', $record->id)
            ->assertJsonPath('data.exact_evidence.0.current_reconciliation.relationship_type', 'exact');
    }

    public function test_variant_and_category_comparable_are_separate_from_exact_and_revision_is_audited(): void
    {
        [$user, , $product] = $this->existingEntities();
        $record = $this->importedRecord();
        $this->signIn($user, ['evidence.reconciliations.view', 'evidence.reconciliations.create', 'evidence.products.view']);
        foreach (['variant_match', 'category_comparable'] as $relationship) {
            $this->postJson($this->url($record), ['relationship_type' => $relationship, 'inventory_object_id' => $product, 'notes' => 'Human review: '.$relationship])->assertCreated();
            $this->getJson("/api/evidence/inventory-objects/{$product}/evidence")->assertOk()
                ->assertJsonCount(0, 'data.exact_evidence')->assertJsonCount(1, 'data.comparable_evidence')
                ->assertJsonCount(0, 'data.evidence_records')
                ->assertJsonPath('data.comparable_evidence.0.current_reconciliation.relationship_type', $relationship);
        }
        $this->getJson($this->url($record))->assertOk()
            ->assertJsonCount(2, 'data.reconciliation_history')
            ->assertJsonPath('data.reconciliation_history.0.relationship_type', 'category_comparable')
            ->assertJsonPath('data.reconciliation_history.1.relationship_type', 'variant_match')
            ->assertJsonPath('data.reconciliation_history.1.reconciliation_status', 'superseded')
            ->assertJsonPath('data.reconciliation_history.1.notes', 'Human review: variant_match')
            ->assertJsonPath('data.reconciliation_history.1.reconciler.id', $user);
        $this->assertSame(1, DB::table('evidence_reconciliations')->where('reconciliation_status', 'active')->count());
        $this->assertNotNull(DB::table('evidence_reconciliations')->where('reconciliation_status', 'superseded')->value('superseded_at'));
    }

    public function test_unresolved_and_rejected_need_no_product_and_are_excluded_from_product_evidence(): void
    {
        [$user, , $product] = $this->existingEntities();
        $record = $this->importedRecord();
        $before = $record->getRawOriginal();
        $this->signIn($user, ['evidence.reconciliations.view', 'evidence.reconciliations.create', 'evidence.products.view']);
        $this->postJson($this->url($record), ['relationship_type' => 'exact', 'inventory_object_id' => $product, 'notes' => 'Initial identification.'])->assertCreated();
        foreach (['unresolved', 'rejected'] as $relationship) {
            $this->postJson($this->url($record), ['relationship_type' => $relationship, 'notes' => 'Insufficient identity evidence.'])->assertCreated()->assertJsonPath('data.inventory_object_id', null);
            $this->getJson('/api/evidence/reconciliations?scope='.$relationship)->assertOk()->assertJsonPath('data.0.id', $record->id);
            $this->getJson("/api/evidence/inventory-objects/{$product}/evidence")->assertOk()
                ->assertJsonCount(0, 'data.exact_evidence')->assertJsonCount(0, 'data.comparable_evidence');
        }
        $this->getJson('/api/evidence/reconciliations')->assertJsonCount(0, 'data');
        $this->getJson('/api/evidence/reconciliations?scope=all')->assertJsonCount(1, 'data');
        $this->assertSame($before, $record->fresh()->getRawOriginal());
    }

    public function test_invalid_combinations_fail_without_superseding_the_current_review(): void
    {
        [$user, , $product, $uom] = $this->existingEntities();
        $record = $this->importedRecord();
        $this->signIn($user, ['evidence.reconciliations.create']);
        $this->postJson($this->url($record), ['relationship_type' => 'unresolved', 'notes' => 'Initial review.'])->assertCreated();
        $otherProduct = DB::table('inventory_objects')->insertGetId(['code' => 'OTHER', 'name' => 'Other', 'unit_id' => DB::table('units')->value('id')]);
        foreach ([
            ['relationship_type' => 'exact'],
            ['relationship_type' => 'variant_match'],
            ['relationship_type' => 'category_comparable'],
            ['relationship_type' => 'rejected', 'inventory_object_id' => $product],
            ['relationship_type' => 'unresolved', 'inventory_object_unit_id' => $uom],
            ['relationship_type' => 'exact', 'inventory_object_id' => $otherProduct, 'inventory_object_unit_id' => $uom],
            ['relationship_type' => 'invented'],
            ['relationship_type' => 'exact', 'inventory_object_id' => $product, 'notes' => ''],
        ] as $payload) {
            $this->postJson($this->url($record), array_replace(['notes' => 'Invalid combination.'], $payload))->assertUnprocessable();
        }
        $this->assertDatabaseCount('evidence_reconciliations', 1);
        $this->assertSame('active', DB::table('evidence_reconciliations')->value('reconciliation_status'));
    }

    public function test_public_and_unpermitted_users_cannot_access_reconciliation_and_viewers_cannot_write(): void
    {
        [$user] = $this->existingEntities();
        $record = $this->importedRecord();
        foreach ([false, true] as $authenticated) {
            if ($authenticated) {
                $this->signIn($user, []);
            }
            $status = $authenticated ? 403 : 401;
            foreach (['/api/evidence/reconciliations', $this->url($record), $this->url($record).'/candidates'] as $url) {
                $this->getJson($url)->assertStatus($status);
            }
            $this->postJson($this->url($record), ['relationship_type' => 'rejected', 'notes' => 'Attempt'])->assertStatus($status);
        }
        $this->signIn($user, ['evidence.reconciliations.view']);
        $this->getJson($this->url($record))->assertOk();
        $this->postJson($this->url($record), ['relationship_type' => 'rejected', 'notes' => 'Attempt'])->assertForbidden();
        $this->assertDatabaseCount('evidence_reconciliations', 0);
    }

    public function test_survey_and_direct_linked_records_cannot_leak_into_external_review(): void
    {
        [$user, , $product] = $this->existingEntities();
        $this->signIn($user, ['evidence.reconciliations.view', 'evidence.reconciliations.create']);
        foreach ([
            ['source_type' => 'consumer_survey', 'source_entity_type' => 'survey_response'],
            ['source_type' => 'market', 'source_entity_type' => 'survey_respondent'],
            ['source_type' => 'market', 'inventory_object_id' => $product],
        ] as $attributes) {
            $record = EvidenceRecord::create([...$attributes, 'epistemic_status' => 'reported', 'recorded_at' => now(), 'raw_payload' => ['profile' => 'private']]);
            $this->getJson($this->url($record))->assertNotFound();
            $this->getJson($this->url($record).'/candidates')->assertNotFound();
            $this->postJson($this->url($record), ['relationship_type' => 'rejected', 'notes' => 'Attempt'])->assertNotFound();
        }
        $this->getJson('/api/evidence/reconciliations?scope=all')->assertJsonCount(0, 'data');
    }

    public function test_pagination_search_and_reimport_preserve_review_and_seed_is_idempotent(): void
    {
        [$user, , $product] = $this->existingEntities();
        $record = $this->importedRecord();
        $this->signIn($user, ['evidence.reconciliations.view', 'evidence.reconciliations.create']);
        $this->postJson($this->url($record), ['relationship_type' => 'exact', 'inventory_object_id' => $product, 'notes' => 'Reviewed manually.'])->assertCreated();
        $same = $this->importedRecord();
        $this->assertSame($record->id, $same->id);
        $this->assertSame('exact', $same->currentReconciliation->relationship_type);
        $this->assertNull($same->inventory_object_id);
        for ($i = 0; $i < 22; $i++) {
            EvidenceRecord::create(['source_type' => 'government', 'epistemic_status' => 'reported', 'recorded_at' => now()]);
        }
        $this->getJson('/api/evidence/reconciliations?page=2&per_page=20')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 22);
        $this->getJson('/api/evidence/reconciliations?scope=invalid')->assertUnprocessable();
        $count = DB::table('permissions')->count();
        $this->seed(EvidencePermissionSeeder::class);
        $this->assertSame($count, DB::table('permissions')->count());
    }

    private function signIn(int $userId, array $abilities): void
    {
        $this->seed(EvidencePermissionSeeder::class);
        $role = DB::table('roles')->insertGetId(['code' => 'reviewer-'.DB::table('roles')->count(), 'name' => 'Reviewer', 'is_active' => true]);
        DB::table('user_roles')->where('user_id', $userId)->delete();
        DB::table('user_roles')->insert(['user_id' => $userId, 'role_id' => $role]);
        foreach ($abilities as $ability) {
            DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => DB::table('permissions')->where('code', $ability)->value('id')]);
        }
        Sanctum::actingAs(User::findOrFail($userId));
    }

    private function importedRecord(): EvidenceRecord
    {
        app(RiceEvidenceImportService::class)->import([[
            'source_name' => 'External market study', 'source_type' => 'market', 'epistemic_status' => 'observed',
            'collection_method' => 'web_observation', 'product_description_raw' => 'Different Rice Variety 25kg',
            'canonical_candidate' => 'Rice - Different Variety', 'package_qty' => 25, 'package_uom' => 'kg',
            'listed_price_php' => 1528, 'normalized_price_php_per_kg' => 61.12, 'channel' => 'retail',
            'observed_period' => '2026-09-01', 'retrieved_date' => '2026-09-08',
            'source_url' => 'https://example.test/market-study', 'location' => 'Manila',
        ]], []);

        return EvidenceRecord::whereNotNull('import_key')->sole();
    }

    private function url(EvidenceRecord $record): string
    {
        return '/api/evidence/reconciliations/'.$record->id;
    }
}
