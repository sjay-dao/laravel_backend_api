<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyUnitApiMigrationTest extends TestCase
{
    public function test_alignment_preserves_legacy_ids_and_does_not_guess_unknown_types(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('type');
        });
        DB::table('units')->insert([['id' => 7, 'code' => 'pc', 'type' => 'count'], ['id' => 19, 'code' => 'custom', 'type' => 'unknown']]);
        $migration = require database_path('migrations/2026_10_02_010000_align_unit_api_columns.php');
        $migration->up();
        $migration->up();
        $this->assertDatabaseHas('units', ['id' => 7, 'code' => 'pc', 'type' => 'count', 'measurement_type' => 'count']);
        $this->assertDatabaseHas('units', ['id' => 19, 'code' => 'custom', 'type' => 'unknown', 'measurement_type' => null]);
        $this->assertSame(2, DB::table('units')->count());
    }
}
