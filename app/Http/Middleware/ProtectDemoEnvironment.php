<?php

namespace App\Http\Middleware;

use App\Support\DemoDatabaseGuard;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class ProtectDemoEnvironment
{
    public function handle(Request $request, Closure $next)
    {
        if (config('demo.enabled') && ! $request->is('up')) {
            try {
                app(DemoDatabaseGuard::class)->assertDedicatedConnection();
            } catch (RuntimeException|InvalidArgumentException $exception) {
                return response()->json(['message' => 'Demo database configuration is unavailable.'], 503);
            }
        }

        return $next($request);
    }
}
