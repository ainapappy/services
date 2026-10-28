<?php

namespace App\Domain\Gateway;

use App\Domain\Gateway\Http\Middleware\AuthenticateApiKey;
use App\Domain\Gateway\Models\ApiKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GatewayServiceProvider extends ServiceProvider
{
    /**
     * Register any domain services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any domain services.
     *
     * The back-office and /api/v1 routes live in the domain, wrapped in
     * the "module:gateway" middleware: disabling the module answers 404
     * on every gateway route without touching the rest of the platform.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->loadRoutes();
    }

    /**
     * Define the per-key rate limiter of the API.
     *
     * The "api" limiter reads the key authenticated by the api.key
     * middleware from the request attributes: its per-key override wins,
     * with the 60 req/min platform default as fallback. Requests without
     * a key (rejected 401 by the middleware right after) fall back to
     * the client IP, so the limiter stays safe outside the gateway.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): array {
            $apiKey = $request->attributes->get(AuthenticateApiKey::ATTRIBUTE);

            if ($apiKey instanceof ApiKey) {
                return [Limit::perMinute($apiKey->rateLimit())->by('api-key:'.$apiKey->id)];
            }

            return [Limit::perMinute(ApiKey::DEFAULT_RATE_LIMIT)->by($request->ip())];
        });
    }

    /**
     * Load the domain route files.
     */
    private function loadRoutes(): void
    {
        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');

        Route::middleware(['api', 'module:gateway', 'api.key'])
            ->prefix('api/v1')
            ->name('api.')
            ->group(__DIR__.'/routes/api.php');
    }
}
