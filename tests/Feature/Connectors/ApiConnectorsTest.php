<?php

use App\Domain\Connectors\Models\Connector;
use App\Domain\Gateway\Models\ApiKey;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'connectors')->update(['enabled' => true]);
});

/**
 * Create an active API key carrying the connectors read ability; the
 * plaintext secret is returned so tests can authenticate with it.
 */
function connectorsReaderSecret(): string
{
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_CONNECTORS_READ]]);

    return $secret;
}

test('the connectors endpoint lists name, type, status and base URL only', function () {
    $secret = connectorsReaderSecret();
    $connector = Connector::factory()->wordpress()->connected()->create(['name' => 'Blog vitrine']);
    Connector::factory()->erpen()->errored()->create(['name' => 'ERP interne']);

    $response = $this->getJson('/api/v1/connectors', withBearer($secret))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Blog vitrine')
        ->assertJsonPath('data.0.type', 'wordpress')
        ->assertJsonPath('data.0.status', 'connected')
        ->assertJsonPath('data.0.baseUrl', 'https://wordpress.test')
        ->assertJsonPath('data.1.name', 'ERP interne')
        ->assertJsonPath('data.1.type', 'erpen')
        ->assertJsonPath('data.1.status', 'error')
        ->assertJsonCount(4, 'data.0');

    expect(str_contains((string) $response->getContent(), 'credentials'))->toBeFalse();
});

test('the plaintext credentials never leak through the API response', function () {
    $secret = connectorsReaderSecret();
    Connector::factory()->wordpress()->create([
        'credentials' => ['username' => 'api-user', 'password' => 'api-leaky-pass'],
    ]);

    $response = $this->getJson('/api/v1/connectors', withBearer($secret))->assertOk();
    $content = (string) $response->getContent();

    expect(str_contains($content, 'api-leaky-pass'))->toBeFalse()
        ->and(str_contains($content, 'api-user'))->toBeFalse()
        ->and(str_contains($content, 'credentials'))->toBeFalse();
});

test('a key without the connectors ability is forbidden', function () {
    $secret = gatewaySecret();
    gatewayKey($secret, ['abilities' => [ApiKey::ABILITY_SERVICES_READ]]);

    $this->getJson('/api/v1/connectors', withBearer($secret))
        ->assertForbidden()
        ->assertJson(['message' => 'API key is missing the required ability.']);
});

test('a key without any ability is forbidden too', function () {
    $secret = gatewaySecret();
    gatewayKey($secret);

    $this->getJson('/api/v1/connectors', withBearer($secret))->assertForbidden();
});

test('a request without any key is unauthorized', function () {
    $this->getJson('/api/v1/connectors')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('an unknown key is unauthorized', function () {
    $this->getJson('/api/v1/connectors', withBearer(gatewaySecret()))
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('disabling the connectors module makes the endpoint 404', function () {
    $secret = connectorsReaderSecret();

    $this->getJson('/api/v1/connectors', withBearer($secret))->assertOk();

    Module::query()->where('slug', 'connectors')->update(['enabled' => false]);

    $this->getJson('/api/v1/connectors', withBearer($secret))->assertNotFound();
});
