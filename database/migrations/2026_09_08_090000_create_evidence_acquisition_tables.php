<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_objects', 'brand')) {
        Schema::table('inventory_objects', function (Blueprint $table) {
            $table->string('brand', 100)->nullable()->after('name');
            $table->string('variant', 150)->nullable()->after('brand');
            $table->string('packaging_description', 150)->nullable()->after('variant');
            $table->text('specification')->nullable()->after('packaging_description');
        });
        }

        if (! Schema::hasTable('evidence_records')) {
        Schema::create('evidence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_object_id')->nullable()->constrained('inventory_objects')->restrictOnDelete();
            $table->string('source_type', 50);
            $table->string('epistemic_status', 30);
            $table->string('collection_method', 50)->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->string('source_entity_type', 100)->nullable();
            $table->unsignedBigInteger('source_entity_id')->nullable();
            $table->dateTime('observed_at')->nullable();
            $table->timestamp('recorded_at');
            $table->string('location_name', 255)->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('context_payload')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['inventory_object_id', 'source_type', 'observed_at'], 'evidence_product_source_date_idx');
            $table->index(['epistemic_status', 'recorded_at'], 'evidence_status_recorded_idx');
        });
        }

        if (! Schema::hasTable('supplier_product_observations')) {
        Schema::create('supplier_product_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_record_id')->unique()->constrained('evidence_records')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
            $table->decimal('purchase_price', 18, 6);
            $table->string('currency_code', 3)->default('PHP');
            $table->decimal('minimum_order_quantity', 18, 6)->nullable();
            $table->decimal('available_quantity', 18, 6)->nullable();
            $table->text('product_specification')->nullable();
            $table->decimal('freight_amount', 18, 6)->nullable();
            $table->string('freight_terms', 255)->nullable();
            $table->string('payment_terms', 150)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->decimal('discount_amount', 18, 6)->nullable();
            $table->text('discount_description')->nullable();
            $table->string('supplier_location', 255)->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'inventory_object_unit_id'], 'supplier_product_observation_idx');
        });
        }

        if (! Schema::hasTable('market_observations')) {
        Schema::create('market_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_record_id')->unique()->constrained('evidence_records')->cascadeOnDelete();
            $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
            $table->string('store_name', 255);
            $table->string('channel', 30);
            $table->decimal('selling_price', 18, 6);
            $table->string('currency_code', 3)->default('PHP');
            $table->string('package_size', 100)->nullable();
            $table->string('availability_status', 50)->nullable();
            $table->text('promotion')->nullable();
            $table->timestamps();
            $table->index(['inventory_object_unit_id', 'channel'], 'market_observation_product_channel_idx');
        });
        }

        if (! Schema::hasTable('surveys')) {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('survey_questions')) {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('question_key', 100);
            $table->text('prompt');
            $table->string('question_type', 50);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('display_order');
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->unique(['survey_id', 'question_key'], 'survey_question_key_unique');
        });
        }

        if (! Schema::hasTable('survey_question_options')) {
        Schema::create('survey_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->string('option_code', 100);
            $table->string('label', 255);
            $table->string('raw_value', 255)->nullable();
            $table->unsignedInteger('display_order');
            $table->timestamps();
            $table->unique(['survey_question_id', 'option_code'], 'survey_question_option_unique');
        });
        }

        if (! Schema::hasTable('survey_respondents')) {
        Schema::create('survey_respondents', function (Blueprint $table) {
            $table->id();
            $table->string('respondent_code', 50)->unique();
            $table->string('session_reference', 100)->nullable()->unique();
            $table->json('profile_payload')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('survey_responses')) {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->restrictOnDelete();
            $table->foreignId('survey_respondent_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['survey_id', 'status', 'submitted_at'], 'survey_response_status_date_idx');
        });
        }

        if (! Schema::hasTable('survey_response_answers')) {
        Schema::create('survey_response_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained()->restrictOnDelete();
            $table->foreignId('survey_question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->json('raw_value')->nullable();
            $table->text('raw_text')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['survey_response_id', 'survey_question_id'], 'survey_response_question_unique');
        });
        }

        if (! Schema::hasTable('survey_scenarios')) {
        Schema::create('survey_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('scenario_code', 50);
            $table->foreignId('inventory_object_id')->constrained('inventory_objects')->restrictOnDelete();
            $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
            $table->decimal('proposed_price', 18, 6);
            $table->string('currency_code', 3)->default('PHP');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['survey_id', 'scenario_code'], 'survey_scenario_code_unique');
        });
        }

        if (! Schema::hasTable('simulated_purchase_events')) {
        Schema::create('simulated_purchase_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_record_id')->unique()->constrained('evidence_records')->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->restrictOnDelete();
            $table->foreignId('survey_respondent_id')->constrained()->restrictOnDelete();
            $table->foreignId('survey_response_id')->constrained()->restrictOnDelete();
            $table->foreignId('survey_scenario_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
            $table->string('source_type', 50)->default('consumer_survey');
            $table->string('epistemic_status', 30)->default('simulated');
            $table->boolean('would_purchase');
            $table->decimal('proposed_price', 18, 6);
            $table->decimal('simulated_quantity', 18, 6)->nullable();
            $table->decimal('base_quantity', 18, 6)->nullable();
            $table->decimal('conversion_factor', 18, 6)->nullable();
            $table->string('frequency_raw', 100)->nullable();
            $table->decimal('estimated_frequency_per_month', 18, 6)->nullable();
            $table->unsignedBigInteger('alternative_inventory_object_id')->nullable();
            $table->text('decision_reason')->nullable();
            $table->json('derived_payload')->nullable();
            $table->timestamp('simulated_at');
            $table->timestamps();
            $table->index(['inventory_object_unit_id', 'simulated_at'], 'simulated_purchase_product_date_idx');
            $table->foreign('alternative_inventory_object_id', 'sim_purchase_alt_inventory_fk')
                ->references('id')->on('inventory_objects')->nullOnDelete();
        });
        } else {
            Schema::table('simulated_purchase_events', function (Blueprint $table) {
                $table->foreign('alternative_inventory_object_id', 'sim_purchase_alt_inventory_fk')
                    ->references('id')->on('inventory_objects')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('simulated_purchase_events');
        Schema::dropIfExists('survey_scenarios');
        Schema::dropIfExists('survey_response_answers');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_respondents');
        Schema::dropIfExists('survey_question_options');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('market_observations');
        Schema::dropIfExists('supplier_product_observations');
        Schema::dropIfExists('evidence_records');

        Schema::table('inventory_objects', function (Blueprint $table) {
            $table->dropColumn(['brand', 'variant', 'packaging_description', 'specification']);
        });
    }
};
