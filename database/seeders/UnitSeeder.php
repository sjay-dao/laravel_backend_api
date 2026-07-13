<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('units')->insert([
            ['code'=>'kg','name'=>'Kilogram','symbol'=>'kg','type'=>'weight'],
            ['code'=>'g','name'=>'Gram','symbol'=>'g','type'=>'weight'],

            ['code'=>'L','name'=>'Liter','symbol'=>'L','type'=>'volume'],
            ['code'=>'ml','name'=>'Milliliter','symbol'=>'ml','type'=>'volume'],

            ['code'=>'pc','name'=>'Piece','symbol'=>'pc','type'=>'count'],
            ['code'=>'pack','name'=>'Pack','symbol'=>'pack','type'=>'package'],
            ['code'=>'box','name'=>'Box','symbol'=>'box','type'=>'package'],
            ['code'=>'tray','name'=>'Tray','symbol'=>'tray','type'=>'package'],
            ['code'=>'sack','name'=>'Sack','symbol'=>'sack','type'=>'package'],
            ['code'=>'case','name'=>'Case','symbol'=>'case','type'=>'package'],
        ]);
    }
}
