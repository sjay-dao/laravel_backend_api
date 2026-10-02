<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('units', 'measurement_type')) {
            Schema::table('units', fn (Blueprint $table) => $table->enum('measurement_type', ['count', 'weight', 'volume', 'length', 'area', 'time'])->nullable());
            if (Schema::hasColumn('units', 'type')) {
                foreach (['count', 'weight', 'volume', 'length', 'area', 'time'] as $type) {
                    DB::table('units')->where('type', $type)->update(['measurement_type' => $type]);
                }
                // Unsupported legacy types stay unknown; do not invent unit conversions.
            }
        }
        if (! Schema::hasColumn('units', 'is_base')) {
            Schema::table('units', fn (Blueprint $table) => $table->boolean('is_base')->default(false));
        }
    }

    public function down(): void
    {
        // Preserve API columns in existing installations, including pre-existing ones.
    }
};
