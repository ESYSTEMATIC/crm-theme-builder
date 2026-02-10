<?php

namespace App\Providers;

use App\Services\HostSiteResolver;
use App\Services\MinioStreamer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HostSiteResolver::class, function () {
            return new HostSiteResolver();
        });

        $this->app->singleton(MinioStreamer::class, function () {
            return new MinioStreamer();
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
