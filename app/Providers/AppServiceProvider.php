<?php

namespace App\Providers;

use App\Domains\Purchasing\Contracts\PurchasingAccountingBoundary;
use App\Domains\Purchasing\Services\NullPurchasingAccountingBoundary;
use App\Domains\System\Models\User;
use App\Domains\System\Services\AuthorizationService;
use Illuminate\Auth\Notifications\ResetPassword;
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

        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.')) {
                return app(AuthorizationService::class)
                    ->can($user, $ability);
            }

            return null;
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL')
                . "/reset-password?token={$token}&email={$user->email}";
        });
    }
}
