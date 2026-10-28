<?php

namespace App\Domain\Gateway\Http\Middleware;

use App\Domain\Gateway\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject the request with a JSON 403 unless the authenticated API key
 * carries the ability passed as parameter (api.ability:services:read).
 */
class EnsureApiKeyAbility
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $apiKey = $request->attributes->get(AuthenticateApiKey::ATTRIBUTE);

        if (! $apiKey instanceof ApiKey || ! $apiKey->hasAbility($ability)) {
            return response()->json(['message' => 'API key is missing the required ability.'], 403);
        }

        return $next($request);
    }
}
