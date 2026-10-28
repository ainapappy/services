<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
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
     *
     * The mail skeleton lives in resources/views/mail/: layout, button,
     * divider and icon are anonymous components used as x-mail::* from
     * every email view, while verify-email, reset-password,
     * monitoring-alert, webhook-failure and test are rendered by their
     * notifications/mailable as plain "mail.*" views.
     *
     * The "x-mail::*" component names are a framework reserved case:
     * they always resolve to the "mail::*" view namespace (the compiler
     * bypasses the anonymous-component guess for them), so the skeleton
     * directory is registered as that namespace here.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(resource_path('views/mail'), 'mail');
    }
}
