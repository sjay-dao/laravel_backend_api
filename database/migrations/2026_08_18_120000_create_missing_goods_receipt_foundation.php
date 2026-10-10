<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number', 50)->unique();
                // The P2P migration adds this supplier constraint after this foundation.
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->foreignId('branch_id')->constrained('branches');
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->date('received_date');
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
                $table->text('remarks')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
                $table->foreignId('inventory_object_id')->constrained('inventory_objects');
                $table->decimal('quantity', 18, 6);
                $table->foreignId('unit_id')->constrained('units');
                $table->decimal('unit_cost', 18, 6);
                $table->decimal('total_cost', 18, 6);
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Existing deployments already own these shared receiving tables.
    }
};
