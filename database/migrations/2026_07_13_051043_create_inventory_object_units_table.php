<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_object_units', function (Blueprint $table) {

            $table->id();

            $table->foreignId('inventory_object_id')
                ->constrained('inventory_objects')
                ->cascadeOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->decimal('conversion_factor', 18, 6)
                ->default(1);

            $table->timestamps();

            $table->unique([
                'inventory_object_id',
                'unit_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_object_units');
    }
};