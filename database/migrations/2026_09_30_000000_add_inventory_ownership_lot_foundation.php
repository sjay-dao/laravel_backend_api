<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_lots')) {
            Schema::create('inventory_lots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_object_id')->constrained('inventory_objects')->restrictOnDelete();
                $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
                $table->string('ownership', 20);
                $table->decimal('quantity_received', 18, 6);
                $table->decimal('quantity_available', 18, 6);
                $table->unsignedBigInteger('settlement_cost_cents');
                $table->date('received_date');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
                $table->index(['inventory_object_id', 'warehouse_id', 'ownership']);
            });
        }

        if (! Schema::hasTable('inventory_lot_movements')) {
            Schema::create('inventory_lot_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->restrictOnDelete();
                $table->foreignId('inventory_movement_item_id')->unique()->constrained('inventory_movement_items')->restrictOnDelete();
                $table->decimal('quantity', 18, 6);
                $table->timestamps();
            });
        }

        // A later sales milestone will write these allocations and update the lot's
        // available quantity in the same transaction as its OUT movement.
        if (! Schema::hasTable('sales_order_lot_allocations')) {
            Schema::create('sales_order_lot_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->restrictOnDelete();
                $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->restrictOnDelete();
                $table->decimal('quantity', 18, 6);
                $table->unsignedBigInteger('settlement_cost_cents');
                $table->timestamps();
                $table->unique(['sales_order_item_id', 'inventory_lot_id'], 'so_item_lot_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lot_allocations');
        Schema::dropIfExists('inventory_lot_movements');
        Schema::dropIfExists('inventory_lots');
    }
};
