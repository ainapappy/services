<?php

namespace App\Core\Modules;

use App\Models\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abort with 404 unless the module passed as parameter is enabled.
 *
 * Registered under the "module" alias: Route::middleware('module:gateway').
 */
class EnsureModuleEnabled
{
    /**
     * Create the middleware.
     */
    public function __construct(private readonly ModuleRegistry $registry) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        if ($this->registry->has($slug) && Module::isEnabled($slug)) {
            return $next($request);
        }

        abort(404);
    }
}
