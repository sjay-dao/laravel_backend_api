<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->enum('status', ['draft', 'posted', 'reversed', 'cancelled'])
                ->default('draft')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft')
                ->change();
        });
    }
};
