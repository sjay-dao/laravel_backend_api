<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('context_payload')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        foreach (['survey_scenarios', 'evidence_records'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('evidence_scenario_id')->nullable()
                    ->constrained('evidence_scenarios')->restrictOnDelete();
            });
        }

        Schema::table('simulated_purchase_events', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable();
            $table->json('unit_snapshot')->nullable();
        });

        // Preserve old IDs and source payloads. Today's product master cannot
        // prove historical unit labels, so old unit snapshots stay unknown.
        DB::table('survey_scenarios')->orderBy('id')->chunkById(200, function ($scenarios) {
            foreach ($scenarios as $scenario) {
                $id = DB::table('evidence_scenarios')->insertGetId([
                    'code' => 'survey-scenario-'.$scenario->id,
                    'name' => $scenario->scenario_code,
                    'description' => $scenario->description,
                    'context_payload' => json_encode(['legacy_survey_scenario_id' => $scenario->id]),
                    'created_at' => $scenario->created_at,
                    'updated_at' => $scenario->updated_at,
                ]);
                DB::table('survey_scenarios')->where('id', $scenario->id)
                    ->update(['evidence_scenario_id' => $id]);
                DB::table('evidence_records')->whereIn('id', function ($query) use ($scenario) {
                    $query->select('evidence_record_id')->from('simulated_purchase_events')
                        ->where('survey_scenario_id', $scenario->id);
                })->update(['evidence_scenario_id' => $id]);
                DB::table('simulated_purchase_events')->where('survey_scenario_id', $scenario->id)
                    ->update(['currency_code' => $scenario->currency_code]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('simulated_purchase_events', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'unit_snapshot']);
        });
        foreach (['evidence_records', 'survey_scenarios'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('evidence_scenario_id');
            });
        }
        Schema::dropIfExists('evidence_scenarios');
    }
};
