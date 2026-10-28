<?php

use App\Domain\Connectors\Http\Controllers\Api\ConnectorController;
use App\Domain\Gateway\Models\ApiKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Connectors — public API routes (/api/v1)
|--------------------------------------------------------------------------
|
| Loaded by ConnectorsServiceProvider under the stateless "api" group
| with the "module:connectors" kill switch. Every route requires a valid
| API key ("api.key", applied on the group) and the ability matching the
| payload ("api.ability:{ability}", per route). Responses are JSON only.
*/

Route::get('connectors', [ConnectorController::class, 'index'])
    ->middleware('api.ability:'.ApiKey::ABILITY_CONNECTORS_READ)
    ->name('index');
