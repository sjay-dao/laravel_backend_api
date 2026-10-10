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
       Schema::create('salary_contracts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('pay_basis', [
                'MONTHLY',
                'DAILY',
                'HOURLY',
                'PIECE_RATE',
                'COMMISSION',
                'MIXED',
            ]);

            $table->decimal('salary_rate', 12, 2);

            $table->date('effective_from');

            $table->date('effective_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->index([
                'employee_id',
                'effective_from'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_contracts');
    }
};
