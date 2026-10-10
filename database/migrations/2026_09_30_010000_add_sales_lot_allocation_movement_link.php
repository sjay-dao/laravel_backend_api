<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_order_lot_allocations', 'inventory_movement_item_id')) {
            Schema::table('sales_order_lot_allocations', function (Blueprint $table) {
                $table->foreignId('inventory_movement_item_id')->nullable()->unique()->after('inventory_lot_id')->constrained('inventory_movement_items')->restrictOnDelete();
            });
        }

        $now = now();
        foreach ([
            ['code' => 'LOT_RECEIPT', 'name' => 'Lot receipt', 'direction' => 'IN', 'description' => 'Inventory received into an ownership lot.'],
            ['code' => 'LOT_SALE_OUT', 'name' => 'Lot sale out', 'direction' => 'OUT', 'description' => 'Inventory issued from an ownership lot for a completed sales order.'],
        ] as $type) {
            DB::table('inventory_movement_types')->updateOrInsert(['code' => $type['code']], [...$type, 'updated_at' => $now, 'created_at' => $now]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_order_lot_allocations', 'inventory_movement_item_id')) {
            Schema::table('sales_order_lot_allocations', function (Blueprint $table) {
                $table->dropForeign(['inventory_movement_item_id']);
                $table->dropUnique(['inventory_movement_item_id']);
                $table->dropColumn('inventory_movement_item_id');
            });
        }
    }
};
