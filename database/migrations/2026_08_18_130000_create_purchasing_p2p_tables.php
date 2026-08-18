<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_requisitions')) {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->date('request_date');
            $table->date('needed_by_date')->nullable();
            $table->text('purpose')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('purchase_requisition_lines')) {
        Schema::create('purchase_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
            $table->foreignId('suggested_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('requested_quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('conversion_factor', 18, 6);
            $table->string('inventory_object_code', 50)->nullable();
            $table->string('inventory_object_name', 255);
            $table->string('unit_code', 20)->nullable();
            $table->string('unit_name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('purchase_orders')) {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('currency_code', 3)->default('PHP');
            $table->string('payment_terms', 100)->nullable();
            $table->string('supplier_reference', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('purchase_order_lines')) {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
            $table->decimal('ordered_quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('conversion_factor', 18, 6);
            $table->decimal('unit_price', 18, 6)->default(0);
            $table->decimal('discount_amount', 18, 6)->default(0);
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('line_total', 18, 6)->default(0);
            $table->date('promised_date')->nullable();
            $table->string('inventory_object_code', 50)->nullable();
            $table->string('inventory_object_name', 255);
            $table->string('unit_code', 20)->nullable();
            $table->string('unit_name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('purchase_order_line_requisition_allocations')) {
        Schema::create('purchase_order_line_requisition_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_line_id');
            $table->unsignedBigInteger('purchase_requisition_line_id');
            $table->decimal('allocated_quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('conversion_factor', 18, 6);
            $table->timestamps();
            $table->unique([
                'purchase_order_line_id',
                'purchase_requisition_line_id',
            ], 'po_line_pr_line_unique');
            $table->foreign('purchase_order_line_id', 'po_pr_alloc_po_line_fk')
                ->references('id')->on('purchase_order_lines')->cascadeOnDelete();
            $table->foreign('purchase_requisition_line_id', 'po_pr_alloc_pr_line_fk')
                ->references('id')->on('purchase_requisition_lines')->restrictOnDelete();
        });
        }

        // These legacy tables are the deployed Goods Receipt foundation. They are
        // intentionally evolved instead of replaced so existing records remain valid.
        if (! Schema::hasColumn('goods_receipts', 'purchase_order_id')) {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('id')
                ->constrained('purchase_orders')->restrictOnDelete();
            $table->dateTime('received_at')->nullable()->after('received_date');
            $table->string('delivery_reference', 100)->nullable()->after('received_at');
            $table->text('inspection_notes')->nullable()->after('remarks');
            $table->foreignId('inventory_movement_id')->nullable()->after('received_by')
                ->unique()->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->after('inventory_movement_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            $table->foreignId('reversed_by')->nullable()->after('posted_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });
        }

        if (! Schema::hasColumn('goods_receipt_items', 'purchase_order_line_id')) {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->foreignId('purchase_order_line_id')->nullable()->after('goods_receipt_id')
                ->constrained('purchase_order_lines')->restrictOnDelete();
            $table->foreignId('inventory_object_unit_id')->nullable()->after('inventory_object_id')
                ->constrained('inventory_object_units')->restrictOnDelete();
            $table->decimal('base_quantity', 18, 6)->nullable()->after('quantity');
            $table->decimal('conversion_factor', 18, 6)->nullable()->after('base_quantity');
            $table->decimal('rejected_quantity', 18, 6)->default(0)->after('conversion_factor');
            $table->decimal('damaged_quantity', 18, 6)->default(0)->after('rejected_quantity');
            $table->string('inventory_object_code', 50)->nullable()->after('total_cost');
            $table->string('inventory_object_name', 255)->nullable()->after('inventory_object_code');
            $table->string('unit_code', 20)->nullable()->after('inventory_object_name');
            $table->string('unit_name', 100)->nullable()->after('unit_code');
        });
        }

        if (! Schema::hasTable('goods_receipt_line_valuations')) {
        Schema::create('goods_receipt_line_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_receipt_item_id')->unique()->constrained('goods_receipt_items')->cascadeOnDelete();
            $table->foreignId('inventory_object_id')->constrained('inventory_objects')->restrictOnDelete();
            $table->decimal('received_quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('conversion_factor', 18, 6);
            $table->decimal('unit_cost', 18, 6);
            $table->decimal('total_cost', 18, 6);
            $table->string('valuation_basis', 50)->default('provisional_receipt_cost');
            $table->boolean('is_inventory_valued')->default(false);
            $table->timestamp('recognized_at');
            $table->timestamps();
        });
        }

        if (! Schema::hasIndex('goods_receipt_line_valuations', 'gr_valuations_receipt_object_idx')) {
            Schema::table('goods_receipt_line_valuations', function (Blueprint $table) {
                $table->index(
                    ['goods_receipt_id', 'inventory_object_id'],
                    'gr_valuations_receipt_object_idx'
                );
            });
        }

        if (! Schema::hasTable('supplier_invoices')) {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('supplier_invoice_number', 100);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('currency_code', 3)->default('PHP');
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('discount_total', 18, 6)->default(0);
            $table->decimal('tax_total', 18, 6)->default(0);
            $table->decimal('total_amount', 18, 6)->default(0);
            $table->boolean('allow_without_receipt')->default(false);
            $table->text('receipt_exception_reason')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['supplier_id', 'supplier_invoice_number'], 'supplier_invoice_number_unique');
        });
        }

        if (! Schema::hasTable('supplier_invoice_lines')) {
        Schema::create('supplier_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_object_unit_id')->nullable()->constrained('inventory_object_units')->restrictOnDelete();
            $table->string('description', 500);
            $table->string('account_treatment', 50)->default('inventory_or_expense');
            $table->decimal('quantity', 18, 6)->default(1);
            $table->decimal('base_quantity', 18, 6)->nullable();
            $table->decimal('conversion_factor', 18, 6)->nullable();
            $table->decimal('unit_price', 18, 6)->default(0);
            $table->decimal('discount_amount', 18, 6)->default(0);
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('line_total', 18, 6)->default(0);
            $table->string('inventory_object_code', 50)->nullable();
            $table->string('inventory_object_name', 255)->nullable();
            $table->string('unit_code', 20)->nullable();
            $table->string('unit_name', 100)->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('supplier_invoice_line_receipt_allocations')) {
        Schema::create('supplier_invoice_line_receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_invoice_line_id');
            $table->unsignedBigInteger('goods_receipt_item_id');
            $table->decimal('matched_quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('applied_unit_cost', 18, 6);
            $table->decimal('applied_amount', 18, 6);
            $table->decimal('price_variance_amount', 18, 6)->default(0);
            $table->timestamps();
            $table->unique([
                'supplier_invoice_line_id',
                'goods_receipt_item_id',
            ], 'invoice_line_receipt_item_unique');
            $table->foreign('supplier_invoice_line_id', 'invoice_gr_alloc_invoice_line_fk')
                ->references('id')->on('supplier_invoice_lines')->cascadeOnDelete();
            $table->foreign('goods_receipt_item_id', 'invoice_gr_alloc_receipt_item_fk')
                ->references('id')->on('goods_receipt_items')->restrictOnDelete();
        });
        }

        if (! Schema::hasTable('supplier_payments')) {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->date('payment_date');
            $table->string('currency_code', 3)->default('PHP');
            $table->string('payment_method', 50);
            $table->string('cash_bank_account_reference', 100);
            $table->string('external_reference', 100)->nullable();
            $table->decimal('total_amount', 18, 6);
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('supplier_payment_allocations')) {
        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('allocated_amount', 18, 6);
            $table->timestamps();
            $table->unique(['supplier_payment_id', 'supplier_invoice_id'], 'payment_invoice_alloc_unique');
        });
        }

        if (! Schema::hasIndex('supplier_payment_allocations', 'payment_invoice_alloc_unique')) {
            Schema::table('supplier_payment_allocations', function (Blueprint $table) {
                $table->unique(
                    ['supplier_payment_id', 'supplier_invoice_id'],
                    'payment_invoice_alloc_unique'
                );
            });
        }

        if (! Schema::hasTable('supplier_returns')) {
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->restrictOnDelete();
            $table->dateTime('return_at');
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('inventory_movement_id')->nullable()->unique()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('supplier_return_lines')) {
        Schema::create('supplier_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_receipt_item_id')->constrained('goods_receipt_items')->restrictOnDelete();
            $table->foreignId('inventory_object_unit_id')->constrained('inventory_object_units')->restrictOnDelete();
            $table->decimal('return_quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('conversion_factor', 18, 6);
            $table->decimal('unit_cost', 18, 6);
            $table->decimal('total_cost', 18, 6);
            $table->string('inventory_object_code', 50)->nullable();
            $table->string('inventory_object_name', 255);
            $table->string('unit_code', 20)->nullable();
            $table->string('unit_name', 100);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_return_lines');
        Schema::dropIfExists('supplier_returns');
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoice_line_receipt_allocations');
        Schema::dropIfExists('supplier_invoice_lines');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('goods_receipt_line_valuations');

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_line_id');
            $table->dropConstrainedForeignId('inventory_object_unit_id');
            $table->dropColumn([
                'base_quantity', 'conversion_factor', 'rejected_quantity',
                'damaged_quantity', 'inventory_object_code',
                'inventory_object_name', 'unit_code', 'unit_name',
            ]);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropConstrainedForeignId('purchase_order_id');
            $table->dropConstrainedForeignId('inventory_movement_id');
            $table->dropConstrainedForeignId('posted_by');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn([
                'received_at', 'delivery_reference', 'inspection_notes',
                'posted_at', 'reversed_at', 'reversal_reason',
            ]);
        });

        Schema::dropIfExists('purchase_order_line_requisition_allocations');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_requisition_lines');
        Schema::dropIfExists('purchase_requisitions');
    }
};
