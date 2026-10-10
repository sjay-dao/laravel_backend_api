<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_order_lot_allocations', 'ownership_snapshot')) {
            Schema::table('sales_order_lot_allocations', function (Blueprint $table) {
                $table->string('ownership_snapshot', 20)->nullable()->after('quantity');
            });

            DB::table('sales_order_lot_allocations')->orderBy('id')->each(function ($allocation) {
                DB::table('sales_order_lot_allocations')->where('id', $allocation->id)->update([
                    'ownership_snapshot' => DB::table('inventory_lots')
                        ->where('id', $allocation->inventory_lot_id)
                        ->value('ownership'),
                ]);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_order_lot_allocations', 'ownership_snapshot')) {
            Schema::table('sales_order_lot_allocations', fn (Blueprint $table) => $table->dropColumn('ownership_snapshot'));
        }
    }
};
