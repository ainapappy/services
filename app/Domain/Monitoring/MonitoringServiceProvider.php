<?php

namespace App\Domain\Monitoring;

use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register any domain services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any domain services.
     *
     * Domain routes will be declared here in later phases, wrapped in the
     * "module:monitoring" middleware.
     */
    public function boot(): void
    {
        //
    }
}
