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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            $table->string('sku')->nullable()->unique();

            $table->string('lot_number')->nullable();

            $table->string('supplier_name')->nullable();

            $table->integer('stock')->default(0);

            $table->decimal('price', 10, 2);

            $table->decimal('cost_price', 10, 2)->nullable();

            $table->date('manufactured_at')->nullable();

            $table->date('expired_at')->nullable();

            $table->string('category')->nullable();

            $table->text('description')->nullable();

            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
