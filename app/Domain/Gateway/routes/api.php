<?php

use App\Domain\Gateway\Http\Controllers\Api\LandingController;
use App\Domain\Gateway\Http\Controllers\Api\ServiceController;
use App\Domain\Gateway\Models\ApiKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gateway — public API routes (/api/v1)
|--------------------------------------------------------------------------
|
| Loaded by GatewayServiceProvider under the stateless "api" group with
| the "module:gateway" kill switch. Every route requires a valid API key
| ("api.key", applied on the group) and the ability matching the payload
| ("api.ability:{ability}", per route). Responses are JSON only.
*/

Route::get('services', [ServiceController::class, 'index'])
    ->middleware('api.ability:'.ApiKey::ABILITY_SERVICES_READ)
    ->name('services.index');

Route::get('landings', [LandingController::class, 'index'])
    ->middleware('api.ability:'.ApiKey::ABILITY_LANDINGS_READ)
    ->name('landings.index');
