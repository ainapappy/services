<?php

namespace App\Domain\Connectors;

use Illuminate\Support\ServiceProvider;

class ConnectorsServiceProvider extends ServiceProvider
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
     * "module:connectors" middleware.
     */
    public function boot(): void
    {
        //
    }
}
