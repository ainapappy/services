<?php

use App\Domain\Monitoring\Http\Controllers\Admin\AlertController;
use App\Domain\Monitoring\Http\Controllers\Admin\HistoryController;
use App\Domain\Monitoring\Http\Controllers\Admin\MonitorController;
use App\Domain\Monitoring\Http\Controllers\Admin\MonitoringController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Monitoring — back-office routes
|--------------------------------------------------------------------------
|
| Loaded by MonitoringServiceProvider. The "module:monitoring" middleware
| answers 404 for every route below as soon as the module is disabled,
| even for authenticated back-office users. Monitoring is a platform
| setting: the "manage-settings" gate restricts it to administrators.
*/

Route::prefix('admin/monitoring')
    ->name('admin.monitoring.')
    ->middleware(['module:monitoring', 'auth', 'verified', 'can:manage-settings'])
    ->group(function (): void {
        Route::get('/', [MonitoringController::class, 'index'])->name('dashboard');

        Route::resource('monitors', MonitorController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['monitors' => 'monitor'])
            ->names('monitors');

        Route::get('history', [HistoryController::class, 'index'])->name('history');

        Route::post('alerts/{monitorAlert}/resolve', [AlertController::class, 'resolve'])
            ->name('alerts.resolve');
    });
