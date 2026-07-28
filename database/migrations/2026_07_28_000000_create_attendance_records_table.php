<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('attendance_records', function (Blueprint $table) {

            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->date('attendance_date');

            $table->enum('status', [
                'PRESENT',
                'ABSENT',
                'LEAVE',
                'HOLIDAY',
                'REST_DAY',
                'HALF_DAY'
            ])->default('PRESENT');

            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();

            $table->integer('worked_minutes')->nullable();
            $table->integer('late_minutes')->nullable();
            $table->integer('undertime_minutes')->nullable();
            $table->integer('overtime_minutes')->nullable();

            $table->unsignedBigInteger('salary_contract_id')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->unique([
                'employee_id',
                'attendance_date'
            ]);
        });
    }
};