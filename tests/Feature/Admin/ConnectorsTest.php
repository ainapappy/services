<?php

use App\Domain\Connectors\Models\Connector;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'connectors')->update(['enabled' => true]);
    $this->withoutVite();
});

function connectorPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Blog vitrine',
        'type' => Connector::TYPE_WORDPRESS,
        'base_url' => 'https://blog.services.test',
        'credentials' => [
            'username' => 'wp-user',
            'password' => 's3cr3t-password',
        ],
    ], $overrides);
}

function connectorsUserWithRole(string $role): User
{
    return $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.connectors.index'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $admin = User::factory()->unverified()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.connectors.index'))
        ->assertRedirect(route('verification.notice'));
});

test('administrators and editors see the connectors index with the exact contract props', function (string $role) {
    $user = connectorsUserWithRole($role);

    $connector = Connector::factory()->wordpress()->connected()->create([
        'name' => 'Blog vitrine',
        'last_tested_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.connectors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Connectors/Index', false)
            ->has('connectors', 1)
            ->where('connectors.0.id', $connector->id)
            ->where('connectors.0.name', 'Blog vitrine')
            ->where('connectors.0.type', 'wordpress')
            ->where('connectors.0.baseUrl', 'https://wordpress.test')
            ->where('connectors.0.status', 'connected')
            ->where('connectors.0.lastTestedAt', $connector->fresh()->last_tested_at?->toISOString())
            ->where('connectors.0.lastError', null)
            ->where('connectors.0.createdAt', $connector->created_at->toISOString())
            ->has('connectors.0', fn (Assert $row) => $row
                ->has('id')
                ->has('name')
                ->has('type')
                ->has('baseUrl')
                ->has('status')
                ->has('lastTestedAt')
                ->has('lastError')
                ->has('createdAt')
                ->missing('credentials')));
})->with(['admin', 'editor']);

test('the create screen renders the form component', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.connectors.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('back/Connectors/Form', false));
});

test('administrators and editors can store a connector of every type', function (string $type, array $credentials) {
    $user = connectorsUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($user)
        ->post(route('admin.connectors.store'), connectorPayload([
            'type' => $type,
            'credentials' => $credentials,
        ]))
        ->assertRedirect(route('admin.connectors.index'));

    $connector = Connector::query()->sole();

    expect($connector->name)->toBe('Blog vitrine')
        ->and($connector->type)->toBe($type)
        ->and($connector->base_url)->toBe('https://blog.services.test')
        ->and($connector->status)->toBe(Connector::STATUS_UNKNOWN)
        ->and($connector->last_tested_at)->toBeNull()
        ->and($connector->last_error)->toBeNull()
        ->and($connector->credentials)->toBe($credentials);
})->with([
    'wordpress' => fn (): array => [Connector::TYPE_WORDPRESS, ['username' => 'wp-user', 'password' => 'app-password-1']],
    'caren' => fn (): array => [Connector::TYPE_CAREN, ['api_key' => 'key-1', 'secret' => 'secret-1']],
    'erpen' => fn (): array => [Connector::TYPE_ERPEN, ['api_key' => 'key-2', 'secret' => 'secret-2']],
    'custom' => fn (): array => [Connector::TYPE_CUSTOM, ['token' => 'free-token', 'extra' => ['a' => 'b']]],
]);

test('the plaintext credentials never reach the database', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.connectors.store'), connectorPayload([
            'credentials' => ['username' => 'wp-user', 'password' => 'ultra-secret-pass'],
        ]));

    $row = (array) DB::table('connectors')->sole();

    expect(str_contains((string) json_encode($row), 'ultra-secret-pass'))->toBeFalse()
        ->and(str_contains((string) json_encode($row), 'wp-user'))->toBeFalse();

    $connector = Connector::query()->sole();

    expect($connector->credentials)->toBe(['username' => 'wp-user', 'password' => 'ultra-secret-pass']);
});

test('invalid payloads are rejected per type', function (array $overrides, string $key) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.connectors.store'), connectorPayload($overrides))
        ->assertSessionHasErrors($key);

    expect(Connector::query()->count())->toBe(0);
})->with([
    'missing username for wordpress' => [
        ['credentials' => ['password' => 'x']],
        'credentials.username',
    ],
    'missing password for wordpress' => [
        ['credentials' => ['username' => 'wp-user']],
        'credentials.password',
    ],
    'missing api_key for caren' => [
        ['type' => Connector::TYPE_CAREN, 'credentials' => ['secret' => 's']],
        'credentials.api_key',
    ],
    'missing secret for caren' => [
        ['type' => Connector::TYPE_CAREN, 'credentials' => ['api_key' => 'k']],
        'credentials.secret',
    ],
    'missing api_key for erpen' => [
        ['type' => Connector::TYPE_ERPEN, 'credentials' => ['secret' => 's']],
        'credentials.api_key',
    ],
    'missing secret for erpen' => [
        ['type' => Connector::TYPE_ERPEN, 'credentials' => ['api_key' => 'k']],
        'credentials.secret',
    ],
    'missing credentials for custom' => [
        ['type' => Connector::TYPE_CUSTOM, 'credentials' => null],
        'credentials',
    ],
    'empty credentials for custom' => [
        ['type' => Connector::TYPE_CUSTOM, 'credentials' => []],
        'credentials',
    ],
    'non array credentials' => [
        ['credentials' => 'wp-user'],
        'credentials',
    ],
    'unknown type' => [
        ['type' => 'shopify'],
        'type',
    ],
    'missing type' => [
        ['type' => null],
        'type',
    ],
    'missing name' => [
        ['name' => null],
        'name',
    ],
    'missing base_url' => [
        ['base_url' => null],
        'base_url',
    ],
    'base_url not an url' => [
        ['base_url' => 'not-an-url'],
        'base_url',
    ],
    'base_url with forbidden scheme' => [
        ['base_url' => 'ftp://blog.services.test'],
        'base_url',
    ],
]);

test('the edit screen receives the connector without any credential', function () {
    $admin = User::factory()->admin()->create();

    $connector = Connector::factory()->wordpress()->create([
        'name' => 'Blog a editer',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.connectors.edit', $connector))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Connectors/Form', false)
            ->where('connector.id', $connector->id)
            ->where('connector.name', 'Blog a editer')
            ->where('connector.type', 'wordpress')
            ->where('connector.baseUrl', 'https://wordpress.test')
            ->has('connector', fn (Assert $form) => $form
                ->has('id')
                ->has('name')
                ->has('type')
                ->has('baseUrl')
                ->missing('credentials')));

    expect(str_contains((string) $response->getContent(), 'credentials'))->toBeFalse();
});

test('updating without credentials keeps the stored payload byte-identical', function () {
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create();

    $cipherBefore = DB::table('connectors')->where('id', $connector->id)->value('credentials');

    $this->actingAs($admin)
        ->put(route('admin.connectors.update', $connector), connectorPayload([
            'name' => 'Nouveau nom',
            'base_url' => 'https://nouveau.services.test',
            'credentials' => ['username' => '', 'password' => ''],
        ]))
        ->assertRedirect(route('admin.connectors.index'));

    $fresh = $connector->fresh();

    expect($fresh->name)->toBe('Nouveau nom')
        ->and($fresh->base_url)->toBe('https://nouveau.services.test')
        ->and($fresh->credentials)->toBe($connector->credentials)
        ->and(DB::table('connectors')->where('id', $connector->id)->value('credentials'))->toBe($cipherBefore);
});

test('provided credential fields replace their counterpart and are re-encrypted', function () {
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create();

    $cipherBefore = DB::table('connectors')->where('id', $connector->id)->value('credentials');

    $this->actingAs($admin)
        ->put(route('admin.connectors.update', $connector), connectorPayload([
            'credentials' => ['password' => 'brand-new-pass'],
        ]))
        ->assertRedirect(route('admin.connectors.index'));

    $row = (array) DB::table('connectors')->sole();
    $fresh = $connector->fresh();

    expect($fresh->credentials)->toBe(['username' => $connector->credentials['username'], 'password' => 'brand-new-pass'])
        ->and($row['credentials'])->not->toBe($cipherBefore)
        ->and(str_contains((string) json_encode($row), 'brand-new-pass'))->toBeFalse();
});

test('a provided custom payload replaces the stored object instead of merging', function () {
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->custom()->create([
        'credentials' => ['legacy_key' => 'stale-value', 'token' => 'old-token'],
    ]);

    $this->actingAs($admin)
        ->put(route('admin.connectors.update', $connector), connectorPayload([
            'type' => Connector::TYPE_CUSTOM,
            'credentials' => ['token' => 'fresh-token'],
        ]))
        ->assertRedirect(route('admin.connectors.index'));

    $fresh = $connector->fresh();

    expect($fresh->credentials)->toBe(['token' => 'fresh-token']);
});

test('administrators and editors can update a connector', function (string $role) {
    $user = connectorsUserWithRole($role);
    $connector = Connector::factory()->caren()->create();

    $this->actingAs($user)
        ->put(route('admin.connectors.update', $connector), connectorPayload([
            'name' => 'Caren renomme',
            'type' => Connector::TYPE_CAREN,
            'credentials' => ['api_key' => 'new-key'],
        ]))
        ->assertRedirect(route('admin.connectors.index'));

    $fresh = $connector->fresh();

    expect($fresh->name)->toBe('Caren renomme')
        ->and($fresh->credentials['api_key'])->toBe('new-key');
})->with(['admin', 'editor']);

test('editors can delete a connector', function () {
    $editor = User::factory()->create();
    $connector = Connector::factory()->erpen()->create();

    $this->actingAs($editor)
        ->delete(route('admin.connectors.destroy', $connector))
        ->assertRedirect(route('admin.connectors.index'));

    expect(Connector::query()->count())->toBe(0);
});

test('disabling the module makes every admin route 404', function () {
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create();

    Module::query()->where('slug', 'connectors')->update(['enabled' => false]);

    $this->actingAs($admin)->get(route('admin.connectors.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('admin.connectors.create'))->assertNotFound();
    $this->actingAs($admin)
        ->post(route('admin.connectors.store'), connectorPayload())
        ->assertNotFound();
    $this->actingAs($admin)
        ->put(route('admin.connectors.update', $connector), connectorPayload())
        ->assertNotFound();
    $this->actingAs($admin)
        ->post(route('admin.connectors.test', $connector))
        ->assertNotFound();
    $this->actingAs($admin)
        ->delete(route('admin.connectors.destroy', $connector))
        ->assertNotFound();
});
