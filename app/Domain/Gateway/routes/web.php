<?php

use App\Domain\Gateway\Http\Controllers\Admin\ApiKeyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gateway — back-office routes
|--------------------------------------------------------------------------
|
| Loaded by GatewayServiceProvider. The "module:gateway" middleware
| answers 404 for every route below as soon as the module is disabled,
| even for authenticated back-office users. API keys are a platform
| setting: the "manage-settings" gate restricts them to administrators.
|
| There is no destroy route on purpose: revocation is the whole life
| cycle, and a revoked key stays in the database for history.
*/

Route::prefix('admin/gateway')
    ->name('admin.gateway.')
    ->middleware(['module:gateway', 'auth', 'verified'])
    ->group(function (): void {
        Route::middleware('can:manage-settings')->group(function (): void {
            Route::get('keys', [ApiKeyController::class, 'index'])->name('keys.index');
            Route::post('keys', [ApiKeyController::class, 'store'])->name('keys.store');
            Route::patch('keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])
                ->name('keys.revoke');
        });
    });
