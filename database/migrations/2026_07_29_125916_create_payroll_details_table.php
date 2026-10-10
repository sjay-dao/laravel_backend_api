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
       Schema::create('payroll_details', function (Blueprint $table) {

            $table->id();

            $table->foreignId('payroll_run_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('salary_contract_id')
                ->nullable()
                ->constrained();

            $table->integer('present_days')->default(0);

            $table->integer('absent_days')->default(0);

            $table->integer('leave_days')->default(0);

            $table->integer('half_days')->default(0);

            $table->integer('worked_minutes')->default(0);

            $table->decimal('basic_pay',12,2)->default(0);

            $table->decimal('allowances',12,2)->default(0);

            $table->decimal('overtime_pay',12,2)->default(0);

            $table->decimal('gross_pay',12,2)->default(0);

            $table->decimal('deductions',12,2)->default(0);

            $table->decimal('net_pay',12,2)->default(0);

            $table->timestamps();

            $table->unique([
                'payroll_run_id',
                'employee_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
