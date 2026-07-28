<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_schedule_assignments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->cascadeOnDelete();

            $table->date('effective_from');

            $table->date('effective_to')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->index([
                'employee_id',
                'effective_from'
            ]);

            $table->index([
                'schedule_id'
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedule_assignments');
    }
};