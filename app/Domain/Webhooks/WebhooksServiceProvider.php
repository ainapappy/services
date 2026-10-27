<?php

namespace App\Domain\Webhooks;

use Illuminate\Support\ServiceProvider;

class WebhooksServiceProvider extends ServiceProvider
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
     * "module:webhooks" middleware.
     */
    public function boot(): void
    {
        //
    }
}
