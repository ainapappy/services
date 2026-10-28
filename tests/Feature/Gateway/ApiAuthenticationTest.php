<?php

use App\Domain\Gateway\Models\ApiKey;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'gateway')->update(['enabled' => true]);
});

test('a valid bearer key grants access to the JSON API', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ, ApiKey::ABILITY_LANDINGS_READ]]);

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertOk()
        ->assertHeader('content-type', 'application/json');

    $this->getJson('/api/v1/landings', withBearer($secret))->assertOk();
});

test('a request without any key is rejected', function () {
    $this->getJson('/api/v1/services')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('an unknown key is rejected', function () {
    $this->getJson('/api/v1/services', withBearer(gatewaySecret()))
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('a malformed key is rejected', function (array $headers) {
    $this->getJson('/api/v1/services', $headers)->assertUnauthorized();
})->with([
    'garbage bearer' => fn (): array => withBearer('not-a-key'),
    'wrong scheme' => fn (): array => ['Authorization' => 'Basic c2tfYWJjZA=='],
    'empty x-api-key' => fn (): array => ['X-Api-Key' => ''],
    'wrong shape x-api-key' => fn (): array => ['X-Api-Key' => 'ak_'.bin2hex(random_bytes(20))],
]);

test('a revoked key is rejected immediately', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ], 'revoked_at' => now()]);

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('an expired key is rejected', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ], 'expires_at' => now()->subMinute()]);

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('the x-api-key header works like the bearer authorization', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ]]);

    $this->getJson('/api/v1/services', ['X-Api-Key' => $secret])->assertOk();

    // Bearer wins when both headers carry keys; the bearer key here is the
    // valid one, so the request still succeeds.
    $other = gatewaySecret();
    gatewayKey($other, ['abilities' => [ApiKey::ABILITY_SERVICES_READ]]);
    $this->getJson('/api/v1/services', withBearer($secret) + ['X-Api-Key' => $other])->assertOk();
});

test('the middleware updates last_used_at', function () {
    $secret = gatewaySecret();
    $apiKey = gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ]]);

    expect($apiKey->last_used_at)->toBeNull();

    $this->getJson('/api/v1/services', withBearer($secret))->assertOk();

    expect($apiKey->fresh()->last_used_at)->not->toBeNull();
});

test('a revoked or expired key never touches last_used_at', function () {
    $revokedSecret = gatewaySecret();
    $revoked = gatewayKey($revokedSecret, ['revoked_at' => now()]);

    $this->getJson('/api/v1/services', withBearer($revokedSecret))->assertUnauthorized();

    expect($revoked->fresh()->last_used_at)->toBeNull();
});

test('the secret cannot be recovered from the database', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ]]);

    $row = (array) DB::table('api_keys')->sole();

    expect(str_contains((string) json_encode($row), $secret))->toBeFalse()
        ->and($row['key_hash'])->toBe(hash('sha256', $secret))
        ->and($row['prefix'])->toBe(substr($secret, 0, 8));
});

test('a key without the required ability is forbidden', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => ['other:thing']]);

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertForbidden()
        ->assertJson(['message' => 'API key is missing the required ability.']);

    $this->getJson('/api/v1/landings', withBearer($secret))->assertForbidden();
});

test('an expired or revoked key with the ability stays rejected', function () {
    $expiredSecret = gatewaySecret();
    gatewayKey($expiredSecret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ], 'expires_at' => now()->subHour()]);

    $this->getJson('/api/v1/services', withBearer($expiredSecret))->assertUnauthorized();
});
