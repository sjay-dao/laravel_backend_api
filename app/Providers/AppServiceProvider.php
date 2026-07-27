<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Policies\EmployeePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL')
                . "/reset-password?token={$token}&email={$user->email}";
        });
    }
}
