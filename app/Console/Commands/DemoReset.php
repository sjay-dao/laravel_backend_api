<?php

namespace App\Console\Commands;

use App\Support\DemoDatabaseGuard;
use Database\Seeders\MicaDemoDatabaseSeeder;
use Illuminate\Console\Command;
use RuntimeException;

class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Explicitly accept deleting all data in the dedicated demo database}';

    protected $description = 'Rebuild an explicitly configured disposable Mica demo database';

    public function handle(DemoDatabaseGuard $guard): int
    {
        try {
            $guard->assertSafe();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->warn('DESTRUCTIVE: all tables and data in '.config('database.connections.'.config('database.default').'.database').' will be deleted and replaced with fictional demo data.');
        if (! $this->option('force')) {
            $this->error('Refused. Supply --force to explicitly accept this demo-only reset.');

            return self::FAILURE;
        }

        return $this->call('migrate:fresh', ['--seed' => true, '--seeder' => MicaDemoDatabaseSeeder::class, '--force' => true]);
    }
}
