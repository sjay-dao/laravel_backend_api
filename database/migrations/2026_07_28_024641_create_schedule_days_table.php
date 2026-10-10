<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_days', function (Blueprint $table) {

            $table->id();

            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->cascadeOnDelete();

            // ISO-8601
            // 1 = Monday
            // ...
            // 7 = Sunday
            $table->unsignedTinyInteger('day_of_week');

            $table->time('start_time')->nullable();

            $table->time('end_time')->nullable();

            $table->unsignedSmallInteger('break_minutes')
                ->default(60);

            $table->unsignedSmallInteger('required_minutes')
                ->default(0);

            $table->boolean('is_rest_day')
                ->default(false);

            $table->string('remarks')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'schedule_id',
                'day_of_week'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_days');
    }
};