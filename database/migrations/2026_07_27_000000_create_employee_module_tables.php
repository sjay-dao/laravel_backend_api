<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('employee_departments')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_positions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained('employee_departments')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no', 50)->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('mobile_number', 50)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('employee_departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('employee_positions')->nullOnDelete();
            $table->string('employment_status', 30)->default('active')->index();
            $table->date('hire_date')->nullable()->index();
            $table->date('regularization_date')->nullable();
            $table->date('separation_date')->nullable();
            $table->string('tin', 50)->nullable();
            $table->string('sss_number', 50)->nullable();
            $table->string('philhealth_number', 50)->nullable();
            $table->string('pagibig_number', 50)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship', 100)->nullable();
            $table->string('mobile_number', 50);
            $table->string('phone_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_salary_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_rate', 15, 2);
            $table->string('rate_type', 20)->default('monthly');
            $table->string('pay_frequency', 20);
            $table->string('currency', 3)->default('PHP');
            $table->decimal('allowance_amount', 15, 2)->default(0);
            $table->date('effective_from')->index();
            $table->date('effective_to')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['employee_id', 'effective_from']);
        });

        Schema::create('employee_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category', 50);
            $table->string('original_name');
            $table->string('path');
            $table->string('disk', 50)->default('local');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no', 50)->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('type', 30)->index();
            $table->date('transaction_date')->index();
            $table->decimal('amount', 15, 2);
            $table->decimal('ledger_effect', 15, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('posted')->index();
            $table->foreignId('reverses_transaction_id')->nullable()->unique()->constrained('employee_transactions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'transaction_date', 'id']);
        });

        Schema::create('employee_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 150);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 100);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('employee_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role_code', 100);
            $table->string('permission', 100);
            $table->timestamps();
            $table->unique(['role_code', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_role_permissions');
        Schema::dropIfExists('employee_activity_logs');
        Schema::dropIfExists('employee_transactions');
        Schema::dropIfExists('employee_attachments');
        Schema::dropIfExists('employee_salary_setups');
        Schema::dropIfExists('employee_emergency_contacts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('employee_positions');
        Schema::dropIfExists('employee_departments');
    }
};
