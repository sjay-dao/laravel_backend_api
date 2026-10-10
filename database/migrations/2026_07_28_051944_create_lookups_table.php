<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookups', function (Blueprint $table) {

            $table->id();

            $table->foreignId('lookup_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code',100);

            $table->string('name');

            $table->text('description')->nullable();

            $table->decimal('value',10,2)
                ->nullable();

            $table->string('color',20)
                ->nullable();

            $table->string('icon',100)
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_system')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'lookup_type_id',
                'code'
            ]);

            $table->index([
                'lookup_type_id',
                'sort_order'
            ]);
            $table->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookups');
    }
};