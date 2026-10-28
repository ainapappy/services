<?php

namespace App\Domain\Monitoring;

use App\Domain\Monitoring\Console\RunMonitoringCommand;
use Illuminate\Support\Facades\Route;
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
     * The back-office routes live in the domain, wrapped in the
     * "module:monitoring" middleware: disabling the module answers 404
     * on every monitoring route without touching the rest of the
     * platform. The alert mail template is a provisional one, namespaced
     * "monitoring::" and owned by the domain — it will be replaced by
     * the definitive mail design in phase 09.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'monitoring');

        $this->commands([RunMonitoringCommand::class]);

        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');
    }
}
