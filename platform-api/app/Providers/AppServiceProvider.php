<?php

namespace App\Providers;

use App\Services\TenantConnectionManager;
use App\Services\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeRegistry::class);

        $this->app->singleton(TenantConnectionManager::class, function () {
            return new TenantConnectionManager();
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
