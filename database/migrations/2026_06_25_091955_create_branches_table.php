<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name', 150);

            $table->string('address')->nullable();

            $table->integer('barangay_id')->nullable();

            $table->foreign('barangay_id')
                ->references('id')
                ->on('psgc_barangay')
                ->nullOnDelete();

            $table->string('branch_type', 50)->nullable();
            $table->string('branch_category', 50)->nullable();

            $table->unsignedInteger('service_bay_count')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};