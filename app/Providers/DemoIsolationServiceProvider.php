<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class DemoIsolationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! config('demo.enabled')) {
            return;
        }
        $name = config('database.default');
        $allowed = $name === 'mysql' || ($name === 'sqlite' && $this->app->environment('testing') && config('database.connections.sqlite.database') === ':memory:');
        config([
            'app.debug' => false,
            'database.connections' => $allowed ? [$name => config("database.connections.$name")] : [],
            'mail.default' => 'array',
            'mail.mailers' => ['array' => ['transport' => 'array']],
            'queue.default' => 'sync',
            'dompdf.options.isRemoteEnabled' => false,
        ]);
    }

    public function boot(): void
    {
        if (config('demo.enabled')) {
            Http::preventStrayRequests();
        }
    }
}
