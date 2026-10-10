<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evidence_reconciliations')) {
            return;
        }

        Schema::create('evidence_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_record_id')
                ->constrained('evidence_records')
                ->cascadeOnDelete();
            $table->foreignId('inventory_object_id')
                ->nullable()
                ->constrained('inventory_objects')
                ->restrictOnDelete();
            $table->foreignId('inventory_object_unit_id')
                ->nullable()
                ->constrained('inventory_object_units')
                ->restrictOnDelete();
            $table->string('relationship_type', 50);
            $table->string('reconciliation_status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->text('rationale')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->foreignId('reconciled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reconciled_at');
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(
                ['evidence_record_id', 'reconciliation_status'],
                'evidence_reconciliation_current_idx'
            );
            $table->index(
                ['inventory_object_id', 'relationship_type', 'reconciliation_status'],
                'evidence_reconciliation_product_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_reconciliations');
    }
};
