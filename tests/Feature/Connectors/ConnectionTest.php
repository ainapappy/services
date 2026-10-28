<?php

use App\Domain\Connectors\Actions\TestConnector;
use App\Domain\Connectors\Jobs\TestConnectorJob;
use App\Domain\Connectors\Models\Connector;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'connectors')->update(['enabled' => true]);
    $this->withoutVite();
});

test('a successful test updates the connector and flashes the real outcome', function () {
    Http::fake(['*' => Http::response(null, 200)]);
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create();

    $this->actingAs($admin)
        ->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('admin.connectors.index'))
        ->assertSessionHas('testResult', fn (array $result): bool => $result['status'] === Connector::STATUS_CONNECTED
            && is_int($result['latencyMs'])
            && $result['latencyMs'] >= 0
            && $result['message'] === null);

    $fresh = $connector->fresh();

    expect($fresh->status)->toBe(Connector::STATUS_CONNECTED)
        ->and($fresh->last_tested_at)->not->toBeNull()
        ->and($fresh->last_error)->toBeNull();
});

test('the flashed test result reaches the index page through the Inertia flash prop', function () {
    Http::fake(['*' => Http::response(null, 200)]);
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->caren()->create();

    $this->actingAs($admin)->post(route('admin.connectors.test', $connector));

    $this->actingAs($admin)
        ->get(route('admin.connectors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Connectors/Index', false)
            ->has('flash.testResult')
            ->where('flash.testResult.status', Connector::STATUS_CONNECTED)
            ->where('flash.testResult.message', null));
});

test('a failed test stores a safe error message without any secret', function () {
    Http::fake(['*' => Http::response('{"message":"Invalid credentials app-password-1"}', 500)]);
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create();

    $this->actingAs($admin)
        ->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('admin.connectors.index'))
        ->assertSessionHas('testResult', fn (array $result): bool => $result['status'] === Connector::STATUS_ERROR
            && $result['message'] === 'HTTP 500 response.');

    $fresh = $connector->fresh();

    expect($fresh->status)->toBe(Connector::STATUS_ERROR)
        ->and($fresh->last_tested_at)->not->toBeNull()
        ->and($fresh->last_error)->toBe('HTTP 500 response.')
        ->and(str_contains((string) $fresh->last_error, 'app-password-1'))->toBeFalse()
        ->and(str_contains((string) $fresh->last_error, 'Invalid credentials'))->toBeFalse();
});

test('an unreachable host yields a generic safe message without exception details', function () {
    Http::fake(['*' => fn (): Response => throw new ConnectionException(
        'cURL error 6: Could not resolve host: wordpress.test'
    )]);
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->erpen()->create();

    $this->actingAs($admin)
        ->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('admin.connectors.index'))
        ->assertSessionHas('testResult', fn (array $result): bool => $result['status'] === Connector::STATUS_ERROR
            && $result['message'] === 'Host unreachable.');

    $fresh = $connector->fresh();

    expect($fresh->status)->toBe(Connector::STATUS_ERROR)
        ->and($fresh->last_error)->toBe('Host unreachable.')
        ->and(str_contains((string) $fresh->last_error, 'cURL'))->toBeFalse()
        ->and(str_contains((string) $fresh->last_error, 'wordpress'))->toBeFalse();
});

test('the health check falls back to GET when the host refuses HEAD', function () {
    Http::fake(function (Request $request) {
        return $request->method() === 'HEAD'
            ? Http::response(null, 405)
            : Http::response(null, 200);
    });
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->custom()->create();

    $this->actingAs($admin)
        ->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('admin.connectors.index'))
        ->assertSessionHas('testResult', fn (array $result): bool => $result['status'] === Connector::STATUS_CONNECTED);

    expect($connector->fresh()->status)->toBe(Connector::STATUS_CONNECTED);
});

test('the wordpress driver probes the REST root', function () {
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create();

    Http::fake(['https://wordpress.test/wp-json/' => Http::response(null, 200)]);

    $this->actingAs($admin)
        ->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('admin.connectors.index'));

    expect($connector->fresh()->status)->toBe(Connector::STATUS_CONNECTED);
});

test('the connection test is queued through the dedicated job', function () {
    Queue::fake();
    config(['queue.default' => 'database']);
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create();

    $this->actingAs($admin)
        ->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('admin.connectors.index'));

    Queue::assertPushed(TestConnectorJob::class, fn (TestConnectorJob $job): bool => $job->connector->is($connector));

    // On a real queue the outcome is not known yet: the flash stays
    // neutral until the worker has run the test.
    $this->followRedirects($this->actingAs($admin)->post(route('admin.connectors.test', $connector)))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('flash.testResult.status', Connector::STATUS_UNKNOWN)
            ->where('flash.testResult.latencyMs', 0)
            ->where('flash.testResult.message', null));
});

test('the queueable job runs the same treatment as the synchronous action', function () {
    Queue::fake();
    $connector = Connector::factory()->wordpress()->create();

    TestConnectorJob::dispatch($connector);

    Queue::assertPushed(TestConnectorJob::class, fn (TestConnectorJob $job): bool => $job->connector->is($connector));

    Http::fake(['https://wordpress.test/wp-json/' => Http::response(null, 200)]);

    (new TestConnectorJob($connector))->handle(app(TestConnector::class));

    $fresh = $connector->fresh();

    expect($fresh->status)->toBe(Connector::STATUS_CONNECTED)
        ->and($fresh->last_tested_at)->not->toBeNull()
        ->and($fresh->last_error)->toBeNull();
});

test('credentials never appear in the job serialization, the model output or the stored error', function () {
    Http::fake(['*' => Http::response('boom', 500)]);
    $admin = User::factory()->admin()->create();
    $connector = Connector::factory()->wordpress()->create([
        'credentials' => ['username' => 'wp-user', 'password' => 'leaky-secret-pass'],
    ]);

    $job = new TestConnectorJob($connector);

    $this->actingAs($admin)->post(route('admin.connectors.test', $connector));

    $serialized = serialize($job);
    $modelJson = (string) $connector->fresh()->toJson();
    $fresh = $connector->fresh();

    expect(str_contains($serialized, 'leaky-secret-pass'))->toBeFalse()
        ->and(str_contains($serialized, 'wp-user'))->toBeFalse()
        ->and(str_contains($modelJson, 'leaky-secret-pass'))->toBeFalse()
        ->and($fresh->toArray())->not->toHaveKey('credentials')
        ->and(str_contains((string) $fresh->last_error, 'leaky-secret-pass'))->toBeFalse()
        ->and(str_contains((string) $fresh->last_error, 'wp-user'))->toBeFalse();
});

test('editors can run the connection test', function () {
    Http::fake(['*' => Http::response(null, 200)]);
    $editor = User::factory()->create();
    $connector = Connector::factory()->caren()->create();

    $this->actingAs($editor)
        ->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('admin.connectors.index'));

    expect($connector->fresh()->status)->toBe(Connector::STATUS_CONNECTED);
});

test('guests are redirected to the login page on the test route', function () {
    $connector = Connector::factory()->wordpress()->create();

    $this->post(route('admin.connectors.test', $connector))
        ->assertRedirect(route('login'));
});
