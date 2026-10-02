<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['delivery_address_id' => 'addresses', 'branch_id' => 'branches'] as $column => $target) {
            $exists = collect(Schema::getForeignKeys('orders'))->contains(fn ($fk) => $fk['columns'] === [$column]);
            if (! $exists) {
                Schema::table('orders', fn (Blueprint $table) => $table->foreign($column)->references('id')->on($target)->nullOnDelete());
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_address_id']);
            $table->dropForeign(['branch_id']);
        });
    }
};
