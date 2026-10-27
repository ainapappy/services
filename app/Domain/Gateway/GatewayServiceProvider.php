<?php

namespace App\Domain\Gateway;

use Illuminate\Support\ServiceProvider;

class GatewayServiceProvider extends ServiceProvider
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
     * "module:gateway" middleware.
     */
    public function boot(): void
    {
        //
    }
}
