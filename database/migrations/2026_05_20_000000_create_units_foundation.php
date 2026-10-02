<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy products are created on 22 May and already refer to units.
        if (! Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name', 100);
                $table->string('symbol', 20);
                $table->enum('measurement_type', ['count', 'weight', 'volume', 'length', 'area', 'time']);
                $table->boolean('is_base')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // A later migration may depend on this shared table.
    }
};
