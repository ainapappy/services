<?php

use App\Core\Modules\EnsureModuleEnabled;
use App\Domain\Gateway\Http\Middleware\AuthenticateApiKey;
use App\Domain\Gateway\Http\Middleware\EnsureApiKeyAbility;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'module' => EnsureModuleEnabled::class,
            'api.key' => AuthenticateApiKey::class,
            'api.ability' => EnsureApiKeyAbility::class,
        ]);

        // Authenticated users hitting guest-only screens (login, register,
        // password reset) land on the back-office dashboard.
        $middleware->redirectUsersTo(fn (): string => route('admin.dashboard'));

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // The "api" group stays stateless (no session, no cookies) and is
        // throttled by the named "api" limiter, which the Gateway domain
        // resolves per API key.
        $middleware->throttleApi();

        // Priority ordering: the module kill switch first, then key
        // authentication, then throttling — the "api" limiter must run after
        // api.key so it can read the authenticated key and apply its
        // per-key limit.
        $middleware->prependToPriorityList(
            before: ThrottleRequests::class,
            prepend: AuthenticateApiKey::class,
        );
        $middleware->prependToPriorityList(
            before: AuthenticateApiKey::class,
            prepend: EnsureModuleEnabled::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The 404s carry the charter of their area: back-office pages
        // under /admin, front pages everywhere else — the API surface
        // keeps its JSON 404 through shouldRenderJsonWhen.
        $exceptions->renderable(function (NotFoundHttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 404
                || $request->is('api/*') || $request->expectsJson()
            ) {
                return null;
            }

            $component = ($request->is('admin') || $request->is('admin/*'))
                ? 'back/NotFound'
                : 'front/NotFound';

            return Inertia::render($component)->toResponse($request)
                ->setStatusCode(404);
        });
    })->create();
