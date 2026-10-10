<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_movement_items')) {
            Schema::create('inventory_movement_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('inventory_movement_id')->constrained('inventory_movements')->restrictOnDelete();
                $t->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
                $t->decimal('quantity', 18, 6);
                $t->text('remarks')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void {}
};
