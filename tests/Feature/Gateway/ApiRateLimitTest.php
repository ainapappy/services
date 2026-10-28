<?php

use App\Domain\Gateway\Models\ApiKey;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'gateway')->update(['enabled' => true]);
});

test('a key limited to two requests is throttled on the third', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ], 'rate_limit' => 2]);

    $this->getJson('/api/v1/services', withBearer($secret))->assertOk();
    $this->getJson('/api/v1/services', withBearer($secret))->assertOk();

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

test('a key without override gets the default sixty requests per minute', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ]]);

    for ($i = 0; $i < 60; $i++) {
        $this->getJson('/api/v1/services', withBearer($secret))->assertOk();
    }

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

test('the throttle rejects with a JSON payload and rate limit headers', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => ['services:read'], 'rate_limit' => 1]);

    $this->getJson('/api/v1/services', withBearer($secret))->assertOk();

    $response = $this->getJson('/api/v1/services', withBearer($secret));

    $response->assertStatus(429);
    expect($response->headers->get('Retry-After'))->toBeNumeric();
    expect($response->headers->get('X-RateLimit-Limit'))->toBe('1');
    expect($response->headers->get('X-RateLimit-Remaining'))->toBe('0');
});

test('each key has its own rate limit bucket', function () {
    $first = gatewaySecret();
    $second = gatewaySecret();
    gatewayKey($first, ['abilities' => [ApiKey::ABILITY_SERVICES_READ], 'rate_limit' => 2]);
    gatewayKey($second, ['abilities' => [ApiKey::ABILITY_SERVICES_READ]]);

    $this->getJson('/api/v1/services', withBearer($first))->assertOk();
    $this->getJson('/api/v1/services', withBearer($first))->assertOk();

    $this->getJson('/api/v1/services', withBearer($first))
        ->assertStatus(429)
        ->assertHeader('Retry-After');

    // The second key keeps its own untouched bucket.
    $this->getJson('/api/v1/services', withBearer($second))->assertOk();
});

test('unauthenticated requests never consume a key bucket', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ], 'rate_limit' => 1]);

    // Failed authentications are rejected before the throttler runs.
    foreach (['', 'sk_'.str_repeat('0', 40)] as $badSecret) {
        $this->getJson('/api/v1/services', withBearer($badSecret))
            ->assertStatus(401);
    }

    // The valid key still gets its full budget.
    $this->getJson('/api/v1/services', withBearer($secret))->assertOk();
    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});
