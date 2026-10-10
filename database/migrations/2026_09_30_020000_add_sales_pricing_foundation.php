<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_objects', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_objects', 'retail_price_cents')) {
                $table->unsignedBigInteger('retail_price_cents')->nullable()->after('is_active');
            }
        });
        Schema::create('inventory_wholesale_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_object_id')->constrained('inventory_objects')->restrictOnDelete();
            $table->decimal('min_quantity', 18, 6);
            $table->decimal('max_quantity', 18, 6)->nullable();
            $table->unsignedBigInteger('unit_price_cents');
            $table->timestamps();
            $table->index(['inventory_object_id', 'min_quantity'], 'idx_wholesale_object_qty');
        });
        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'sale_type')) {
                $table->string('sale_type', 20)->default('RETAIL')->after('status_id');
            }
        });
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_order_items', 'list_unit_price_cents')) {
                $table->unsignedBigInteger('list_unit_price_cents')->nullable()->after('quantity');
                $table->unsignedBigInteger('discount_cents')->default(0)->after('list_unit_price_cents');
                $table->unsignedBigInteger('final_unit_price_cents')->nullable()->after('discount_cents');
                $table->unsignedBigInteger('line_total_cents')->nullable()->after('final_unit_price_cents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropColumn(['list_unit_price_cents', 'discount_cents', 'final_unit_price_cents', 'line_total_cents']);
        });
        Schema::table('sales_orders', fn (Blueprint $table) => $table->dropColumn('sale_type'));
        Schema::dropIfExists('inventory_wholesale_price_tiers');
        Schema::table('inventory_objects', fn (Blueprint $table) => $table->dropColumn('retail_price_cents'));
    }
};
