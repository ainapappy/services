<?php

use App\Domain\Landing\Http\Controllers\Admin\LandingPageController;
use App\Domain\Landing\Models\LandingPage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing — back-office routes
|--------------------------------------------------------------------------
|
| Loaded by LandingServiceProvider. The "module:landing" middleware answers
| 404 for every route below as soon as the module is disabled, even for
| authenticated back-office users.
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['module:landing', 'auth', 'verified'])
    ->group(function (): void {
        Route::resource('landing', LandingPageController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['landing' => 'landingPage'])
            ->middlewareFor('index', 'can:viewAny,'.LandingPage::class)
            ->middlewareFor(['create', 'store'], 'can:create,'.LandingPage::class)
            ->middlewareFor(['edit', 'update'], 'can:update,landingPage')
            ->middlewareFor('destroy', 'can:delete,landingPage');

        Route::patch('landing/{landingPage}/publish', [LandingPageController::class, 'publish'])
            ->middleware('can:update,landingPage')
            ->name('landing.publish');
    });
