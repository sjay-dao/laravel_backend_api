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
       Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('order_number')->unique();
            $table->string('supplier_name');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();

            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');

            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
