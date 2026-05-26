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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_type', [
                'cash',
                'scheduled',
                'external_installment'
            ])->default('cash')->after('status');

            $table->enum('payment_status', [
                'unpaid',
                'pending',
                'partial',
                'paid',
                'overdue',
                'cancelled'
            ])->default('unpaid')->after('payment_type');

            $table->date('payment_due_date')->nullable()->after('payment_status');

            $table->string('external_payment_reference')->nullable()->after('payment_due_date');

            $table->string('cancellation_reason')->nullable()->after('remarks');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'payment_status',
                'payment_due_date',
                'external_payment_reference',
                'cancellation_reason',
                'cancelled_at',
            ]);
        });
    }
};
