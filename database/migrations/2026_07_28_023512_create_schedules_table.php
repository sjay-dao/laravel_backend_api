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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->enum('type', [
                'FIXED',
                'FLEXIBLE',
                'ON_CALL',
                'SHIFT',
                'FIELD'
            ]);

            $table->unsignedSmallInteger('weekly_required_minutes')->default(0);

            $table->unsignedSmallInteger('minimum_daily_minutes')->nullable();

            $table->unsignedSmallInteger('maximum_daily_minutes')->nullable();

            $table->time('earliest_start')->nullable();

            $table->time('latest_start')->nullable();

            $table->time('latest_end')->nullable();

            $table->time('core_start')->nullable();

            $table->time('core_end')->nullable();

            $table->boolean('approval_required')->default(false);

            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_type');
    }
};
