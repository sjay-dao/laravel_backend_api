<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait BuildsEvidenceTestDatabase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createExistingErpTablesRequiredByEvidence();
        $migration = require database_path(
            'migrations/2026_09_08_090000_create_evidence_acquisition_tables.php'
        );
        $migration->up();
        foreach ([
            '2026_09_08_100000_add_import_key_to_evidence_records_table.php',
            '2026_09_08_110000_create_evidence_reconciliations_table.php',
            '2026_09_09_090000_add_evidence_simulation_foundation.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'evidence_reconciliations',
            'evidence_scenarios',
            ...$this->additionalOperationalTables(),
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
            'role_permissions',
            'user_roles',
            'permissions',
            'roles',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    private function additionalOperationalTables(): array
    {
        return ['purchase_orders', 'goods_receipts', 'supplier_invoices', 'supplier_payments',
            'payments', 'accounting_entries', 'journal_entries', 'inventory_stocks', 'cash_movements'];
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
            $table->decimal('conversion_factor', 18, 6)->nullable()->default(1);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('resource');
            $table->string('action');
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });

        // Sentinel business tables make the separation assertion explicit.
        foreach (['sales_orders', 'orders', 'inventory_movements', ...$this->additionalOperationalTables()] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }

    private function existingEntities(): array
    {
        $timestamp = now();
        $userId = DB::table('users')->insertGetId([
            'name' => 'Evidence Collector',
            'email' => 'collector@example.test',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $unitId = DB::table('units')->insertGetId([
            'code' => 'KG',
            'name' => 'Kilogram',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $productId = DB::table('inventory_objects')->insertGetId([
            'code' => 'RICE-A',
            'name' => 'Rice A',
            'unit_id' => $unitId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $productUnitId = DB::table('inventory_object_units')->insertGetId([
            'inventory_object_id' => $productId,
            'unit_id' => $unitId,
            'conversion_factor' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $supplierId = DB::table('suppliers')->insertGetId([
            'code' => 'SUP-RICE',
            'name' => 'Rice Supplier',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return [$userId, $supplierId, $productId, $productUnitId];
    }
}
