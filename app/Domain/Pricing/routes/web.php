<?php

use App\Domain\Pricing\Http\Controllers\Admin\PlanController;
use App\Domain\Pricing\Http\Controllers\Admin\TableController;
use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pricing — back-office routes
|--------------------------------------------------------------------------
|
| Loaded by PricingServiceProvider. The "module:pricing" middleware answers
| 404 for every route below as soon as the module is disabled, even for
| authenticated back-office users.
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['module:pricing', 'auth', 'verified'])
    ->group(function (): void {
        Route::resource('pricing/plans', PlanController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['plans' => 'plan'])
            ->names('pricing.plans')
            ->middlewareFor('index', 'can:viewAny,'.PricingPlan::class)
            ->middlewareFor(['create', 'store'], 'can:create,'.PricingPlan::class)
            ->middlewareFor(['edit', 'update'], 'can:update,plan')
            ->middlewareFor('destroy', 'can:delete,plan');

        Route::patch('pricing/plans/{plan}/toggle', [PlanController::class, 'toggle'])
            ->middleware('can:update,plan')
            ->name('pricing.plans.toggle');

        Route::resource('pricing/tables', TableController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['tables' => 'pricingTable'])
            ->names('pricing.tables')
            ->middlewareFor('index', 'can:viewAny,'.PricingTable::class)
            ->middlewareFor(['create', 'store'], 'can:create,'.PricingTable::class)
            ->middlewareFor(['edit', 'update'], 'can:update,pricingTable')
            ->middlewareFor('destroy', 'can:delete,pricingTable');

        Route::patch('pricing/tables/{pricingTable}/publish', [TableController::class, 'publish'])
            ->middleware('can:update,pricingTable')
            ->name('pricing.tables.publish');
    });
