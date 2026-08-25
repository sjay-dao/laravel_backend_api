<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_order_id')
                ->constrained('sales_orders')
                ->cascadeOnDelete();

            $table->foreignId('inventory_id')
                ->constrained('inventory_objects')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('inventory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};