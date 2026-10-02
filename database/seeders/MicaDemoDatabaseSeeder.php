<?php

namespace Database\Seeders;

use App\Support\DemoDatabaseGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MicaDemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(DemoDatabaseGuard::class)->assertSafe();
        Carbon::withTestNow(Carbon::parse(config('demo.date').' 12:00:00', config('app.timezone')), function () {
            DB::transaction(function () {
                $this->call([ReferenceDatabaseSeeder::class, DemoAccessSeeder::class, MicaEbikeDemoSeeder::class, MicaDemoTransactionsSeeder::class]);
            });
        });
    }
}
