<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structure only: no geography or personal data is imported from dumps.
        if (! Schema::hasTable('psgc_region')) {
            Schema::create('psgc_region', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->string('prefix')->nullable();
                $table->string('description');
                $table->unsignedInteger('created_by')->default(0);
                $table->dateTime('created_date')->nullable();
            });
        }
        if (! Schema::hasTable('psgc_province')) {
            Schema::create('psgc_province', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->string('prefix');
                $table->string('description');
                $table->string('region_prefix');
                $table->unsignedInteger('created_by')->default(0);
                $table->dateTime('created_date')->nullable();
            });
        }
        if (! Schema::hasTable('psgc_city_mun')) {
            Schema::create('psgc_city_mun', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->string('prefix');
                $table->string('description');
                $table->string('region_prefix');
                $table->string('province_prefix');
                $table->string('zip_code')->nullable();
                $table->unsignedInteger('created_by')->default(0);
                $table->dateTime('created_date')->nullable();
            });
        }
        if (! Schema::hasTable('psgc_barangay')) {
            Schema::create('psgc_barangay', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->string('description');
                $table->string('region_prefix');
                $table->string('province_prefix');
                $table->string('city_mun_prefix');
                $table->unsignedInteger('created_by')->default(0);
                $table->dateTime('created_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Existing installations may own these shared tables. Never drop them here.
    }
};
