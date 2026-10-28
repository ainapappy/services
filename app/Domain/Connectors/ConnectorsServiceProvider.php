<?php

namespace App\Domain\Connectors;

use App\Domain\Connectors\Models\Connector;
use App\Domain\Connectors\Policies\ConnectorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ConnectorsServiceProvider extends ServiceProvider
{
    /**
     * Register any domain services.
     */
    public function register(): void
    {
        $this->app->singleton(ConnectorManager::class);
    }

    /**
     * Bootstrap any domain services.
     *
     * The back-office and /api/v1 routes live in the domain, wrapped in
     * the "module:connectors" middleware: disabling the module answers
     * 404 on every connectors route without touching the rest of the
     * platform.
     */
    public function boot(): void
    {
        Gate::policy(Connector::class, ConnectorPolicy::class);

        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');

        Route::middleware(['api', 'module:connectors', 'api.key'])
            ->prefix('api/v1')
            ->name('api.connectors.')
            ->group(__DIR__.'/routes/api.php');
    }
}
