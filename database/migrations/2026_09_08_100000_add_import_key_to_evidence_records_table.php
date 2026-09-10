<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A stable import key lets source-backed evidence be safely replayed
     * without turning a newer observation into an update of an older one.
     */
    public function up(): void
    {
        if (! Schema::hasTable('evidence_records')
            || Schema::hasColumn('evidence_records', 'import_key')) {
            return;
        }

        Schema::table('evidence_records', function (Blueprint $table) {
            $table->string('import_key', 64)->nullable();
            $table->unique('import_key', 'evidence_records_import_key_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('evidence_records')
            || ! Schema::hasColumn('evidence_records', 'import_key')) {
            return;
        }

        Schema::table('evidence_records', function (Blueprint $table) {
            $table->dropUnique('evidence_records_import_key_unique');
            $table->dropColumn('import_key');
        });
    }
};
