<?php

use App\Domain\Gateway\Models\ApiKey;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'gateway')->update(['enabled' => true]);
    $this->withoutVite();
});

function apiKeyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Integration partner',
        'abilities' => ['services:read', 'landings:read'],
    ], $overrides);
}

function gatewayUserWithRole(string $role): User
{
    return $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.gateway.keys.index'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $admin = User::factory()->unverified()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.gateway.keys.index'))
        ->assertRedirect(route('verification.notice'));
});

test('editors are forbidden from every key management route', function (string $verb, string $route, array $payload) {
    $editor = gatewayUserWithRole(User::ROLE_EDITOR);

    $response = match ($verb) {
        'get' => $this->actingAs($editor)->get($route),
        'post' => $this->actingAs($editor)->post($route, $payload),
        'patch' => $this->actingAs($editor)->patch($route, $payload),
    };

    $response->assertForbidden();
})->with([
    'index' => fn (): array => ['get', route('admin.gateway.keys.index'), []],
    'store' => fn (): array => ['post', route('admin.gateway.keys.store'), apiKeyPayload()],
    'revoke' => fn (): array => ['patch', route('admin.gateway.keys.revoke', ApiKey::factory()->create()), []],
]);

test('administrators see the keys index with the exact contract props', function () {
    $admin = gatewayUserWithRole(User::ROLE_ADMIN);

    $key = ApiKey::factory()->create([
        'name' => 'Historic key',
        'abilities' => ['services:read'],
        'last_used_at' => now()->subHour(),
        'revoked_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.gateway.keys.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Gateway/Keys', false)
            ->has('keys', 1)
            ->where('keys.0.id', $key->id)
            ->where('keys.0.name', 'Historic key')
            ->where('keys.0.prefix', $key->prefix)
            ->where('keys.0.abilities', ['services:read'])
            ->where('keys.0.lastUsedAt', $key->last_used_at?->toISOString())
            ->where('keys.0.revokedAt', $key->revoked_at?->toISOString())
            ->where('keys.0.expiresAt', $key->expires_at?->toISOString())
            ->where('keys.0.createdAt', $key->created_at->toISOString()));
});

test('administrators create a key and the secret is flashed exactly once', function () {
    $admin = gatewayUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.gateway.keys.store'), apiKeyPayload([
            'rate_limit' => 120,
            'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
        ]))
        ->assertRedirect(route('admin.gateway.keys.index'));

    $key = ApiKey::query()->sole();

    $captured = null;
    $this->actingAs($admin)
        ->get(route('admin.gateway.keys.index'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$captured, $key): void {
            $page->component('back/Gateway/Keys', false)
                ->where('flash.secret', function (mixed $secret) use (&$captured, $key): bool {
                    $captured = $secret;

                    return is_string($secret)
                        && preg_match('/^sk_[0-9a-f]{40}$/', $secret) === 1
                        && $key->prefix === substr($secret, 0, 8)
                        && $key->key_hash === hash('sha256', $secret);
                });
        });

    expect($key->name)->toBe('Integration partner')
        ->and($key->abilities)->toBe(['services:read', 'landings:read'])
        ->and($key->rate_limit)->toBe(120)
        ->and($key->expires_at)->not->toBeNull()
        ->and($key->revoked_at)->toBeNull()
        ->and($captured)->not->toBeNull();

    // A second visit never carries the secret again.
    $this->actingAs($admin)
        ->get(route('admin.gateway.keys.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('flash.secret', null));
});

test('the plaintext secret never reaches the database', function () {
    $admin = gatewayUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.gateway.keys.store'), apiKeyPayload())
        ->assertRedirect(route('admin.gateway.keys.index'));

    $captured = null;
    $this->actingAs($admin)
        ->get(route('admin.gateway.keys.index'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$captured): void {
            $page->where('flash.secret', function (mixed $secret) use (&$captured): bool {
                $captured = $secret;

                return is_string($secret);
            });
        });

    $row = (array) DB::table('api_keys')->sole();

    expect($captured)->toBeString()
        ->and(str_contains((string) json_encode($row), $captured))->toBeFalse()
        ->and($row['key_hash'])->toBe(hash('sha256', $captured))
        ->and($row['prefix'])->toBe(substr($captured, 0, 8));
});

test('invalid payloads are rejected', function (array $overrides, string $key) {
    $admin = gatewayUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.gateway.keys.store'), apiKeyPayload($overrides))
        ->assertSessionHasErrors($key);
})->with([
    'missing name' => [['name' => null], 'name'],
    'missing abilities' => [['abilities' => []], 'abilities'],
    'non array abilities' => [['abilities' => 'services:read'], 'abilities'],
    'bad ability format' => [['abilities' => ['Services']], 'abilities.0'],
    'bad rate limit' => [['rate_limit' => 'lots'], 'rate_limit'],
    'past expiry' => [['expires_at' => now()->subDay()->format('Y-m-d H:i:s')], 'expires_at'],
]);

test('administrators revoke a key and revocation is idempotent', function () {
    $admin = gatewayUserWithRole(User::ROLE_ADMIN);
    $apiKey = ApiKey::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.gateway.keys.revoke', $apiKey))
        ->assertRedirect(route('admin.gateway.keys.index'));

    $revokedAt = $apiKey->fresh()->revoked_at;
    expect($revokedAt)->not->toBeNull();

    $this->actingAs($admin)->patch(route('admin.gateway.keys.revoke', $apiKey));

    expect($apiKey->fresh()->revoked_at->equalTo($revokedAt))->toBeTrue();
});

test('revoking keeps the key in the database for history', function () {
    $admin = gatewayUserWithRole(User::ROLE_ADMIN);
    $apiKey = ApiKey::factory()->create();

    $this->actingAs($admin)->patch(route('admin.gateway.keys.revoke', $apiKey));

    expect(ApiKey::query()->count())->toBe(1)
        ->and($apiKey->fresh()->isActive())->toBeFalse();
});

test('disabling the module makes the admin routes 404', function () {
    $admin = gatewayUserWithRole(User::ROLE_ADMIN);
    $apiKey = ApiKey::factory()->create();

    Module::query()->where('slug', 'gateway')->update(['enabled' => false]);

    $this->actingAs($admin)->get(route('admin.gateway.keys.index'))->assertNotFound();
    $this->actingAs($admin)
        ->post(route('admin.gateway.keys.store'), apiKeyPayload())
        ->assertNotFound();
    $this->actingAs($admin)
        ->patch(route('admin.gateway.keys.revoke', $apiKey))
        ->assertNotFound();
});
