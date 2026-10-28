<?php

namespace App\Domain\Gateway\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The internal health probe of the gateway: a minimal JSON answer,
 * latency-measurable by the monitoring module.
 *
 * It deliberately sits OUTSIDE the API key and the per-key rate
 * limiting — a probe needs no credential and must never be throttled —
 * but stays inside the "module:gateway" kill switch: disabling the
 * gateway answers 404 and the gateway monitor reads it as down.
 */
class HealthController extends Controller
{
    /**
     * Answer the health probe.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
