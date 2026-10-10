<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->uuid('payment_no')->unique();
            $table->string('method', 30);
            $table->unsignedBigInteger('tendered_amount_cents');
            $table->unsignedBigInteger('applied_amount_cents');
            $table->unsignedBigInteger('change_cents')->default(0);
            $table->string('reference', 150)->nullable();
            $table->timestamp('received_at');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['sales_order_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_payments');
    }
};
