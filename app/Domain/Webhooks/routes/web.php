<?php

use App\Domain\Webhooks\Http\Controllers\Admin\DeliveryController;
use App\Domain\Webhooks\Http\Controllers\Admin\EndpointController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks — back-office routes
|--------------------------------------------------------------------------
|
| Loaded by WebhooksServiceProvider. The "module:webhooks" middleware
| answers 404 for every route below as soon as the module is disabled,
| even for authenticated back-office users. Endpoints and deliveries are
| platform settings: the "manage-settings" gate restricts them to
| administrators.
*/

Route::prefix('admin/webhooks')
    ->name('admin.webhooks.')
    ->middleware(['module:webhooks', 'auth', 'verified', 'can:manage-settings'])
    ->group(function (): void {
        Route::resource('endpoints', EndpointController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['endpoints' => 'webhookEndpoint'])
            ->names('endpoints');

        Route::post('endpoints/{webhookEndpoint}/regenerate', [EndpointController::class, 'regenerate'])
            ->name('endpoints.regenerate');

        Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{webhookDelivery}', [DeliveryController::class, 'show'])->name('deliveries.show');
        Route::post('deliveries/{webhookDelivery}/retry', [DeliveryController::class, 'retry'])
            ->name('deliveries.retry');
    });
