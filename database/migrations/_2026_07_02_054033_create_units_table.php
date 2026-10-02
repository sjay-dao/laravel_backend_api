<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('units')) {
            return;
        }
        (require __DIR__.'/2026_05_20_000000_create_units_foundation.php')->up();
    }

    public function down(): void
    {
        // Shared units are owned by the early foundation; do not drop them here.
    }
};
