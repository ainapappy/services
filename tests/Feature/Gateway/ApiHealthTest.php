<?php

use App\Models\Module;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'gateway')->update(['enabled' => true]);
});

test('the internal health endpoint answers 200 without an API key', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

test('the health route keeps the module kill switch and drops the key and the throttle', function () {
    $route = Route::getRoutes()->getByName('api.health');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('api/v1/health')
        ->and($route->gatherMiddleware())->toContain('module:gateway')
        ->and($route->gatherMiddleware())->not->toContain('api.key')
        ->and($route->excludedMiddleware())->toContain('throttle:api');
});

test('the health endpoint is never throttled', function () {
    // The "api" limiter falls back to 60 requests per minute per IP: a
    // throttled route would answer 429 before the 61st probe.
    for ($i = 0; $i < 65; $i++) {
        $this->getJson('/api/v1/health')->assertOk();
    }
});

test('disabling the gateway module answers 404 on the health endpoint', function () {
    Module::query()->where('slug', 'gateway')->update(['enabled' => false]);

    $this->getJson('/api/v1/health')->assertNotFound();
});
