<?php

namespace Tests\Feature;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Services\RiceEvidenceImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RiceEvidenceImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createExistingErpTablesRequiredByEvidence();

        $evidenceMigration = require database_path(
            'migrations/2026_09_08_090000_create_evidence_acquisition_tables.php'
        );
        $evidenceMigration->up();

        $importKeyMigration = require database_path(
            'migrations/2026_09_08_100000_add_import_key_to_evidence_records_table.php'
        );
        $importKeyMigration->up();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'simulated_purchase_events',
            'survey_scenarios',
            'survey_response_answers',
            'survey_responses',
            'survey_respondents',
            'survey_question_options',
            'survey_questions',
            'surveys',
            'market_observations',
            'supplier_product_observations',
            'evidence_records',
            'inventory_object_units',
            'inventory_objects',
            'suppliers',
            'units',
            'users',
            'sales_orders',
            'orders',
            'inventory_movements',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_import_preserves_provenance_and_raw_values_for_an_ambiguous_product(): void
    {
        $this->createExistingRiceProduct();
        $row = $this->marketRow();

        $result = app(RiceEvidenceImportService::class)->import([$row], []);

        $record = EvidenceRecord::query()->sole();

        $this->assertCount(1, $result['market']['imported']);
        $this->assertCount(1, $result['market']['unresolved']);
        $this->assertNull($record->inventory_object_id);
        $this->assertSame('market', $record->source_type);
        $this->assertSame('observed', $record->epistemic_status);
        $this->assertSame('web_observation', $record->collection_method);
        $this->assertSame($row['source_url'], $record->source_reference);
        $this->assertSame('2026-09-08 00:00:00', $record->observed_at?->format('Y-m-d H:i:s'));
        $this->assertNotNull($record->recorded_at);
        $this->assertSame($row['product_description_raw'], $record->raw_payload['product_description_raw']);
        $this->assertSame($row['retrieved_date'], $record->raw_payload['retrieved_date']);
        $this->assertSame($row['source_url'], $record->context_payload['market_context']['source_url']);
        $this->assertSame(65.1, (float) $record->context_payload['market_context']['normalized_price_php_per_kg']);
        $this->assertSame('unresolved', $record->context_payload['product_reconciliation']['status']);
        $this->assertSame('Rice - Jasmine', $record->context_payload['product_reconciliation']['canonical_candidate']);
        $this->assertDatabaseCount('market_observations', 0);
    }

    public function test_unknown_supplier_commercial_values_remain_unpersisted_and_are_never_zeroed(): void
    {
        $lead = [
            'supplier_name' => 'Megarich Rice Center Corp.',
            'location' => 'Bacoor, Cavite',
            'known_moq' => 'Wholesale price advertised at minimum 20 sacks x 25kg',
            'price' => 'UNKNOWN - contact supplier',
            'source_url' => 'https://example.test/megarich-rice-center',
            'notes' => 'Direct commercial collection is still required.',
            '_input_source' => 'supplier_leads.csv',
        ];

        $result = app(RiceEvidenceImportService::class)->import([], [$lead]);

        $unpersistedLead = $result['supplier_leads']['unpersisted'][0];

        $this->assertCount(1, $result['supplier_leads']['unpersisted']);
        $this->assertSame('UNKNOWN - contact supplier', $unpersistedLead['commercial_price_raw']);
        $this->assertSame('UNKNOWN - contact supplier', $unpersistedLead['raw']['price']);
        $this->assertSame(0, DB::table('suppliers')->count());
        $this->assertSame(0, DB::table('supplier_product_observations')->count());
        $this->assertSame(0, DB::table('evidence_records')->count());
    }

    public function test_import_never_creates_operational_sales_orders_or_inventory_movements(): void
    {
        $this->createExistingRiceProduct();

        app(RiceEvidenceImportService::class)->import([$this->marketRow()], [
            [
                'supplier_name' => 'Unverified Rice Lead',
                'price' => 'UNKNOWN',
                'source_url' => 'https://example.test/unverified-rice-lead',
            ],
        ]);

        $this->assertSame(0, DB::table('sales_orders')->count());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('inventory_movements')->count());
    }

    public function test_rerunning_the_same_import_does_not_duplicate_evidence(): void
    {
        $this->createExistingRiceProduct();
        $row = $this->marketRow();
        $importer = app(RiceEvidenceImportService::class);

        $first = $importer->import([$row], []);
        $firstRecordId = $first['market']['imported'][0]['evidence_record_id'];
        $second = $importer->import([$row], []);

        $this->assertSame(1, DB::table('evidence_records')->count());
        $this->assertCount(1, $second['market']['existing']);
        $this->assertCount(0, $second['market']['imported']);
        $this->assertSame($firstRecordId, $second['market']['existing'][0]['evidence_record_id']);
    }

    /**
     * Create a rice-family candidate that is deliberately not an exact match
     * for the imported canonical product, so no unsafe product mapping occurs.
     */
    private function createExistingRiceProduct(): void
    {
        $timestamp = now();
        $unitId = DB::table('units')->insertGetId([
            'code' => 'KG',
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'measurement_type' => 'weight',
            'is_base' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $productId = DB::table('inventory_objects')->insertGetId([
            'code' => 'RICE-EXISTING',
            'name' => 'Maharlika Rice',
            'unit_id' => $unitId,
            'track_inventory' => true,
            'is_sellable' => true,
            'is_active' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('inventory_object_units')->insert([
            'inventory_object_id' => $productId,
            'unit_id' => $unitId,
            'conversion_factor' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function marketRow(): array
    {
        return [
            'source_name' => 'Example Rice Marketplace',
            'source_type' => 'market',
            'epistemic_status' => 'observed',
            'collection_method' => 'web_observation',
            'product_description_raw' => 'Jasmine Rice Premium 5 kg',
            'canonical_candidate' => 'Rice - Jasmine',
            'package_qty' => 5,
            'package_uom' => 'kg',
            'listed_price_php' => 325.50,
            'normalized_price_php_per_kg' => 65.10,
            'channel' => 'retail',
            'location' => 'Dasmariñas, Cavite',
            'observed_period' => '2026-09-08',
            'retrieved_date' => '2026-09-08',
            'source_url' => 'https://example.test/listings/jasmine-rice-5kg',
            'notes' => 'Observed listed price, not a checkout or transaction price.',
            '_input_source' => 'market_observations.csv',
        ];
    }

    private function createExistingErpTablesRequiredByEvidence(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->string('measurement_type')->nullable();
            $table->boolean('is_base')->default(false);
            $table->timestamps();
        });

        Schema::create('inventory_objects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->foreignId('unit_id')->constrained('units');
            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_object_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_object_id')->constrained('inventory_objects');
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('conversion_factor', 18, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        // Sentinel business tables make the non-transactional import contract explicit.
        foreach (['sales_orders', 'orders', 'inventory_movements'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
}
