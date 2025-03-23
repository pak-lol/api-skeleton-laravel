<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TokenService;
use App\Services\RateLimitService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService();
        });

        $this->app->singleton(RateLimitService::class, function ($app) {
            return new RateLimitService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
