<?php

namespace App\Domain\Landing;

use Illuminate\Support\ServiceProvider;

class LandingServiceProvider extends ServiceProvider
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
     * "module:landing" middleware.
     */
    public function boot(): void
    {
        //
    }
}
