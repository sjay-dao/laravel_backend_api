<?php

namespace App\Providers;

use App\Domains\Purchasing\Contracts\PurchasingAccountingBoundary;
use App\Domains\Purchasing\Services\NullPurchasingAccountingBoundary;
use App\Domains\System\Models\User;
use App\Domains\System\Services\AuthorizationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PurchasingAccountingBoundary::class,
            NullPurchasingAccountingBoundary::class
        );
    }

    public function boot(): void
    {
        $proxies = config('app.trusted_proxies', '');
        TrustProxies::at($proxies === '*' ? '*' : array_values(array_filter(explode(',', (string) $proxies))));

        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.')) {
                return app(AuthorizationService::class)
                    ->can($user, $ability);
            }

            return null;
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url')
                ."/reset-password?token={$token}&email={$user->email}";
        });
    }
}
