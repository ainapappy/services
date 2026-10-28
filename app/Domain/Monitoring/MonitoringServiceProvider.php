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
     * platform. The alert mail renders the project mail skeleton
     * (resources/views/mail/), shared by every email of the platform.
     */
    public function boot(): void
    {
        $this->commands([RunMonitoringCommand::class]);

        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');
    }
}
