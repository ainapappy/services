<?php

namespace App\Domain\Webhooks;

use App\Domain\Connectors\Events\ConnectorStatusChanged;
use App\Domain\Landing\Events\LandingPublished;
use App\Domain\Pricing\Events\PricingTablePublished;
use App\Domain\Webhooks\Listeners\WebhookDispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
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
     * The dispatcher is subscribed explicitly to the domain events of the
     * other modules; a new event is supported by adding one mapping in
     * the WebhookEvent enum and one listen() call here. The back-office
     * routes live in the domain, wrapped in the "module:webhooks"
     * middleware: disabling the module answers 404 on every webhook route
     * without touching the rest of the platform.
     */
    public function boot(): void
    {
        Event::listen(LandingPublished::class, WebhookDispatcher::class);
        Event::listen(PricingTablePublished::class, WebhookDispatcher::class);
        Event::listen(ConnectorStatusChanged::class, WebhookDispatcher::class);

        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');
    }
}
