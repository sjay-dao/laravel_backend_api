<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! collect(Schema::getForeignKeys('employees'))->contains(fn ($fk) => $fk['columns'] === ['employment_status_id'])) {
            Schema::table('employees', fn (Blueprint $table) => $table->foreign('employment_status_id')->references('id')->on('lookups'));
        }
        if (! collect(Schema::getForeignKeys('users'))->contains(fn ($fk) => $fk['columns'] === ['employee_id'])) {
            Schema::table('users', fn (Blueprint $table) => $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete());
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['employee_id']));
        Schema::table('employees', fn (Blueprint $table) => $table->dropForeign(['employment_status_id']));
    }
};
