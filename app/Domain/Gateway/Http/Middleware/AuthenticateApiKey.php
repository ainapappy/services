<?php

namespace App\Domain\Gateway\Http\Middleware;

use App\Domain\Gateway\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate an incoming request with an API key.
 *
 * The plaintext secret is read from "Authorization: Bearer …" or from the
 * "X-Api-Key" header. A missing, malformed, unknown, revoked or expired
 * key is answered with a JSON 401. The matched model is stored on the
 * request attributes (AuthenticateApiKey::ATTRIBUTE) for the rate limiter
 * and the ability middleware.
 */
class AuthenticateApiKey
{
    /**
     * The request attribute under which the authenticated key is stored.
     */
    final public const ATTRIBUTE = 'apiKey';

    /**
     * The exact shape of a plaintext secret: "sk_" + 40 hex chars.
     */
    private const SECRET_PATTERN = '/^sk_[0-9a-f]{40}$/';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->extractSecret($request);

        if ($secret === null || preg_match(self::SECRET_PATTERN, $secret) !== 1) {
            return $this->reject();
        }

        $hash = ApiKey::hashSecret($secret);
        $apiKey = ApiKey::query()->where('key_hash', $hash)->first();

        if (! $apiKey instanceof ApiKey) {
            return $this->reject();
        }

        // Constant-time comparison of the stored hash against the one
        // computed from the presented secret.
        if (! hash_equals($apiKey->key_hash, $hash) || ! $apiKey->isActive()) {
            return $this->reject();
        }

        // Discreet save: one small indexed UPDATE per authenticated call,
        // which keeps "last used" exact without delaying the response.
        $apiKey->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set(self::ATTRIBUTE, $apiKey);

        return $next($request);
    }

    /**
     * Extract the plaintext secret from the request headers.
     */
    private function extractSecret(Request $request): ?string
    {
        $bearer = $request->bearerToken();

        if (is_string($bearer) && $bearer !== '') {
            return $bearer;
        }

        $header = trim((string) $request->header('X-Api-Key', ''));

        return $header === '' ? null : $header;
    }

    /**
     * The uniform rejection: every failure is a bare JSON 401.
     */
    private function reject(): Response
    {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
