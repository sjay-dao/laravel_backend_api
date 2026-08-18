<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 255);
            $table->string('contact_person', 255)->nullable();
            $table->string('contact_number', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->integer('barangay_id')->nullable();
            $table->foreign('barangay_id')
                ->references('id')
                ->on('psgc_barangay')
                ->nullOnDelete();
            $table->string('tin', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};