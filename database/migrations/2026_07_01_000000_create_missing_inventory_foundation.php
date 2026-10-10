<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->text('description')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('inventory_objects')) {
            Schema::create('inventory_objects', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->foreignId('inventory_category_id')->nullable()->constrained('inventory_categories')->restrictOnDelete();
                $t->foreignId('unit_id')->constrained('units')->restrictOnDelete();
                $t->boolean('track_inventory')->default(true);
                $t->boolean('is_sellable')->default(true);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        foreach (['brand', 'variant', 'packaging_description', 'specification'] as $column) {
            if (! Schema::hasColumn('inventory_objects', $column)) {
                Schema::table('inventory_objects', fn (Blueprint $t) => $t->string($column)->nullable());
            }
        }
        if (! Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $t) {
                $t->id();
                $t->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
                $t->string('code')->unique();
                $t->string('name');
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('inventory_movement_types')) {
            Schema::create('inventory_movement_types', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->string('direction');
                $t->text('description')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $t) {
                $t->id();
                $t->string('transaction_no', 50)->unique();
                $t->foreignId('movement_type_id')->constrained('inventory_movement_types');
                $t->dateTime('movement_date');
                $t->foreignId('branch_id')->nullable()->constrained('branches');
                $t->foreignId('warehouse_id')->nullable()->constrained('warehouses');
                $t->string('reference_type', 100)->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->text('remarks')->nullable();
                $t->foreignId('created_by')->nullable()->constrained('users');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('inventory_reservations')) {
            Schema::create('inventory_reservations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('inventory_object_id')->constrained('inventory_objects');
                $t->foreignId('warehouse_id')->nullable()->constrained('warehouses');
                $t->decimal('quantity', 18, 6);
                $t->string('reference_type', 100)->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->text('remarks')->nullable();
                $t->boolean('is_released')->default(false);
                $t->unsignedBigInteger('created_by')->nullable();
                $t->unsignedBigInteger('released_by')->nullable();
                $t->timestamp('released_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void {}
};
