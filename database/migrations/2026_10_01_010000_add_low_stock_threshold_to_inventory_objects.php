<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_objects', function (Blueprint $table) {
            $table->decimal('low_stock_threshold', 18, 6)->nullable()->after('retail_price_cents');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_objects', fn (Blueprint $table) => $table->dropColumn('low_stock_threshold'));
    }
};
