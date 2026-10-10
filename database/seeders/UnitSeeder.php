<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['pc', 'Piece', 'count'], ['kg', 'Kilogram', 'weight'], ['g', 'Gram', 'weight'], ['L', 'Liter', 'volume'], ['ml', 'Milliliter', 'volume']] as [$code, $name, $type]) {
            DB::table('units')->updateOrInsert(['code' => $code], ['name' => $name, 'symbol' => $code, 'measurement_type' => $type, 'is_base' => in_array($code, ['pc', 'kg', 'L'], true), 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
