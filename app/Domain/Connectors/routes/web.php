<?php

use App\Domain\Connectors\Http\Controllers\Admin\ConnectorController;
use App\Domain\Connectors\Models\Connector;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Connectors — back-office routes
|--------------------------------------------------------------------------
|
| Loaded by ConnectorsServiceProvider. The "module:connectors" middleware
| answers 404 for every route below as soon as the module is disabled,
| even for authenticated back-office users.
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['module:connectors', 'auth', 'verified'])
    ->group(function (): void {
        Route::resource('connectors', ConnectorController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['connectors' => 'connector'])
            ->middlewareFor('index', 'can:viewAny,'.Connector::class)
            ->middlewareFor(['create', 'store'], 'can:create,'.Connector::class)
            ->middlewareFor(['edit', 'update'], 'can:update,connector')
            ->middlewareFor('destroy', 'can:delete,connector');

        Route::post('connectors/{connector}/test', [ConnectorController::class, 'test'])
            ->middleware('can:test,connector')
            ->name('connectors.test');
    });
