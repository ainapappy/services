<?php

namespace App\Http\Middleware;

use App\Core\Modules\ModuleDefinition;
use App\Core\Modules\ModuleRegistry;
use App\Models\Module;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $enabled = Module::query()->pluck('enabled', 'slug');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'role', 'email_verified_at']),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'modules' => collect(app(ModuleRegistry::class)->all())
                ->map(fn (ModuleDefinition $module): array => [
                    'slug' => $module->slug,
                    'enabled' => (bool) $enabled->get($module->slug, false),
                ])
                ->values()
                ->all(),
            // Flashed once by the Gateway key store action: the freshly
            // created secret crosses the wire a single time and is then
            // gone from the session.
            'flash' => [
                'secret' => fn (): ?string => is_string($secret = $request->session()->get('secret')) ? $secret : null,
                // Flashed once by the Connectors connection test: the
                // status, the latency and a safe error message. Never
                // the credentials.
                'testResult' => fn (): ?array => is_array($result = $request->session()->get('testResult')) ? $result : null,
            ],
        ];
    }
}
