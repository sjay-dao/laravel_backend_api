<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)->unique();      // kg, g, L, ml, pc
            $table->string('name', 100);               // Kilogram
            $table->string('symbol', 20);              // kg
            $table->string('type', 30);                // weight, volume, count, package

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};