<?php

use App\Domain\Connectors\Models\Connector;
use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorAlert;
use App\Domain\Monitoring\Models\MonitorCheck;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'monitoring')->update(['enabled' => true]);
    $this->withoutVite();
});

function monitorPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Blog WordPress',
        'type' => Monitor::TYPE_CONNECTOR,
        'target' => ['connectorId' => Connector::factory()->create()->id],
        'latency_threshold_ms' => null,
        'active' => true,
    ], $overrides);
}

function monitoringUserWithRole(string $role): User
{
    return $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.monitoring.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.monitoring.monitors.index'))->assertRedirect(route('login'));
    $this->get(route('admin.monitoring.history'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $admin = User::factory()->unverified()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.monitoring.dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('editors are forbidden from every monitoring route', function (string $verb, string $route, array $payload) {
    $editor = monitoringUserWithRole(User::ROLE_EDITOR);

    $response = match ($verb) {
        'get' => $this->actingAs($editor)->get($route),
        'post' => $this->actingAs($editor)->post($route, $payload),
        'put' => $this->actingAs($editor)->put($route, $payload),
        'delete' => $this->actingAs($editor)->delete($route),
    };

    $response->assertForbidden();
})->with([
    'dashboard' => fn (): array => ['get', route('admin.monitoring.dashboard'), []],
    'monitors index' => fn (): array => ['get', route('admin.monitoring.monitors.index'), []],
    'monitors create' => fn (): array => ['get', route('admin.monitoring.monitors.create'), []],
    'monitors store' => fn (): array => ['post', route('admin.monitoring.monitors.store'), monitorPayload()],
    'monitors edit' => fn (): array => ['get', route('admin.monitoring.monitors.edit', Monitor::factory()->create()), []],
    'monitors update' => fn (): array => ['put', route('admin.monitoring.monitors.update', Monitor::factory()->create()), monitorPayload()],
    'monitors destroy' => fn (): array => ['delete', route('admin.monitoring.monitors.destroy', Monitor::factory()->create()), []],
    'history' => fn (): array => ['get', route('admin.monitoring.history'), []],
    'alerts resolve' => fn (): array => ['post', route('admin.monitoring.alerts.resolve', MonitorAlert::factory()->create()), []],
]);

test('administrators see the dashboard with the exact contract props', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    $connector = Connector::factory()->create(['name' => 'Blog WordPress']);
    $monitor = Monitor::factory()->forConnector($connector)->create([
        'name' => 'Blog monitor',
        'latency_threshold_ms' => 200,
    ]);

    MonitorCheck::factory()
        ->for($monitor)
        ->count(22)
        ->sequence(fn (Sequence $sequence): array => [
            'checked_at' => now()->subMinutes(22 - $sequence->index),
            'latency_ms' => 100 + $sequence->index,
        ])
        ->create();

    MonitorAlert::factory()->for($monitor)->create();
    MonitorAlert::factory()->for($monitor)->warning()->resolved()->create();

    $this->actingAs($admin)
        ->get(route('admin.monitoring.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Monitoring/Dashboard', false)
            ->has('monitors', 1)
            ->where('monitors.0.id', $monitor->id)
            ->where('monitors.0.name', 'Blog monitor')
            ->where('monitors.0.type', 'connector')
            ->where('monitors.0.active', true)
            ->where('monitors.0.latencyThresholdMs', 200)
            ->where('monitors.0.targetSummary', 'Blog WordPress')
            ->where('monitors.0.status', 'up')
            ->where('monitors.0.latencyMs', 121)
            ->where('monitors.0.checkedAt', $monitor->latestCheck->checked_at->toISOString())
            ->where('monitors.0.message', null)
            ->where('monitors.0.sparkline', collect(range(102, 121))
                ->map(fn (int $latencyMs): array => ['latencyMs' => $latencyMs])
                ->all())
            ->has('monitors.0.alerts', 1)
            ->where('monitors.0.alerts.0.level', 'critical')
            ->where('monitors.0.alerts.0.message', 'HTTP 500 response.')
            ->where('connectors.0.id', $connector->id)
            ->where('connectors.0.name', 'Blog WordPress'));
});

test('a monitor never checked shows a null current state on the dashboard', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    Monitor::factory()->gateway()->create();

    $this->actingAs($admin)
        ->get(route('admin.monitoring.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('monitors.0.status', null)
            ->where('monitors.0.latencyMs', null)
            ->where('monitors.0.checkedAt', null)
            ->where('monitors.0.message', null)
            ->where('monitors.0.sparkline', [])
            ->where('monitors.0.alerts', []));
});

test('administrators see the monitors list with the last state of each monitor', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    $gateway = Monitor::factory()->gateway()->create(['name' => 'Gateway']);
    MonitorCheck::factory()->for($gateway)->degraded()->create(['checked_at' => now()->subMinute()]);
    Monitor::factory()->webhook()->create(['name' => 'Webhooks']);

    $this->actingAs($admin)
        ->get(route('admin.monitoring.monitors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Monitoring/Monitors', false)
            ->has('monitors', 2)
            ->where('monitors.0.name', 'Gateway')
            ->where('monitors.0.type', 'gateway')
            ->where('monitors.0.active', true)
            ->where('monitors.0.latencyThresholdMs', null)
            ->where('monitors.0.targetSummary', 'Gateway health endpoint')
            ->where('monitors.0.lastStatus', 'degraded')
            ->where('monitors.0.lastCheckedAt', $gateway->latestCheck->checked_at->toISOString())
            ->where('monitors.1.name', 'Webhooks')
            ->where('monitors.1.lastStatus', null)
            ->where('monitors.1.lastCheckedAt', null));
});

test('administrators open the monitor creation form with the connector options', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    Connector::factory()->create(['name' => 'Blog WordPress']);

    $this->actingAs($admin)
        ->get(route('admin.monitoring.monitors.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Monitoring/MonitorForm', false)
            ->missing('monitor')
            ->where('connectors.0.name', 'Blog WordPress'));
});

test('administrators create a connector monitor with a normalized target', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    $connector = Connector::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.monitoring.monitors.store'), [
            'name' => 'Blog monitor',
            'type' => Monitor::TYPE_CONNECTOR,
            'target' => ['connectorId' => $connector->id],
            'latency_threshold_ms' => 250,
            'active' => true,
        ])
        ->assertRedirect(route('admin.monitoring.monitors.index'));

    $monitor = Monitor::query()->sole();

    expect($monitor->name)->toBe('Blog monitor')
        ->and($monitor->type)->toBe(Monitor::TYPE_CONNECTOR)
        ->and($monitor->target)->toBe(['connectorId' => $connector->id])
        ->and($monitor->latency_threshold_ms)->toBe(250)
        ->and($monitor->active)->toBeTrue();
});

test('a gateway or webhook monitor stores an empty target', function (string $type) {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.monitoring.monitors.store'), [
            'name' => ucfirst($type).' monitor',
            'type' => $type,
            'active' => true,
        ])
        ->assertRedirect(route('admin.monitoring.monitors.index'));

    expect(Monitor::query()->sole()->target)->toBe([]);
})->with(['gateway', 'webhook']);

test('invalid monitor payloads are rejected', function (array $overrides, string $key) {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.monitoring.monitors.store'), monitorPayload($overrides))
        ->assertSessionHasErrors($key);
})->with([
    'missing name' => [['name' => null], 'name'],
    'unknown type' => [['type' => 'uptime'], 'type'],
    'missing connector id' => [['target' => null], 'target.connectorId'],
    'unknown connector' => [['target' => ['connectorId' => 99999]], 'target.connectorId'],
    'negative threshold' => [['latency_threshold_ms' => -5], 'latency_threshold_ms'],
    'non integer threshold' => [['latency_threshold_ms' => 'fast'], 'latency_threshold_ms'],
    'missing active' => [['active' => null], 'active'],
    'non boolean active' => [['active' => 'oui'], 'active'],
]);

test('administrators open the edition form with the monitor and connector options', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    $connector = Connector::factory()->create(['name' => 'Blog WordPress']);
    $monitor = Monitor::factory()->forConnector($connector)->create(['latency_threshold_ms' => 200]);

    $this->actingAs($admin)
        ->get(route('admin.monitoring.monitors.edit', $monitor))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Monitoring/MonitorForm', false)
            ->where('monitor.id', $monitor->id)
            ->where('monitor.name', $monitor->name)
            ->where('monitor.type', 'connector')
            ->where('monitor.active', true)
            ->where('monitor.latencyThresholdMs', 200)
            ->where('monitor.target.connectorId', $connector->id)
            ->where('connectors.0.name', 'Blog WordPress'));
});

test('a partial update keeps the omitted fields', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    $connector = Connector::factory()->create();
    $monitor = Monitor::factory()->forConnector($connector)->threshold(300)->create();

    $this->actingAs($admin)
        ->put(route('admin.monitoring.monitors.update', $monitor), [
            'name' => 'Renamed',
            'active' => false,
        ])
        ->assertRedirect(route('admin.monitoring.monitors.index'));

    $fresh = $monitor->fresh();

    expect($fresh->name)->toBe('Renamed')
        ->and($fresh->active)->toBeFalse()
        ->and($fresh->type)->toBe(Monitor::TYPE_CONNECTOR)
        ->and($fresh->target)->toBe(['connectorId' => $connector->id])
        ->and($fresh->latency_threshold_ms)->toBe(300);
});

test('switching the type to connector requires a connector id', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    $monitor = Monitor::factory()->gateway()->create();

    $this->actingAs($admin)
        ->put(route('admin.monitoring.monitors.update', $monitor), ['type' => Monitor::TYPE_CONNECTOR])
        ->assertSessionHasErrors('target.connectorId');
});

test('switching away from the connector type empties the target', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    $monitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();

    $this->actingAs($admin)
        ->put(route('admin.monitoring.monitors.update', $monitor), ['type' => Monitor::TYPE_WEBHOOK])
        ->assertRedirect(route('admin.monitoring.monitors.index'));

    expect($monitor->fresh()->target)->toBe([])
        ->and($monitor->fresh()->type)->toBe(Monitor::TYPE_WEBHOOK);
});

test('an update replaces the targeted connector while staying connector', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    $monitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();
    $newConnector = Connector::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.monitoring.monitors.update', $monitor), [
            'target' => ['connectorId' => $newConnector->id],
        ])
        ->assertRedirect(route('admin.monitoring.monitors.index'));

    expect($monitor->fresh()->type)->toBe(Monitor::TYPE_CONNECTOR)
        ->and($monitor->fresh()->target)->toBe(['connectorId' => $newConnector->id]);
});

test('deleting a monitor destroys its journal and alerts with it', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    $monitor = Monitor::factory()->create();
    MonitorCheck::factory()->for($monitor)->count(3)->create();
    MonitorAlert::factory()->for($monitor)->count(2)->create();

    $this->actingAs($admin)
        ->delete(route('admin.monitoring.monitors.destroy', $monitor))
        ->assertRedirect(route('admin.monitoring.monitors.index'));

    expect(Monitor::query()->count())->toBe(0)
        ->and(MonitorCheck::query()->count())->toBe(0)
        ->and(MonitorAlert::query()->count())->toBe(0);
});

test('administrators browse the checks journal and filter it', function (array $query, int $expected, ?string $type, ?string $status, ?int $days) {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    $connectorMonitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();
    $gatewayMonitor = Monitor::factory()->gateway()->create();

    MonitorCheck::factory()->for($connectorMonitor)->create();
    MonitorCheck::factory()->for($connectorMonitor)->down()->create();
    MonitorCheck::factory()->for($gatewayMonitor)->degraded()->create();
    MonitorCheck::factory()->for($connectorMonitor)->down()->create(['checked_at' => now()->subDays(40)]);

    $this->actingAs($admin)
        ->get(route('admin.monitoring.history', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Monitoring/History', false)
            ->has('checks', $expected)
            ->where('filters.type', $type)
            ->where('filters.status', $status)
            ->where('filters.days', $days));
})->with([
    'no filter' => [[], 4, null, null, null],
    'by type' => [['type' => 'gateway'], 1, 'gateway', null, null],
    'by status' => [['status' => 'down'], 2, null, 'down', null],
    'by period' => [['days' => 7], 3, null, null, 7],
    'unknown status is ignored' => [['status' => 'invented'], 4, null, null, null],
    'zero days is ignored' => [['days' => 0], 4, null, null, null],
]);

test('the journal lines carry the monitor name and type', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);

    $monitor = Monitor::factory()->forConnector(Connector::factory()->create(['name' => 'Blog']))->create();
    $check = MonitorCheck::factory()->for($monitor)->degraded()->create();

    $this->actingAs($admin)
        ->get(route('admin.monitoring.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('checks.0.id', $check->id)
            ->where('checks.0.monitorName', $monitor->name)
            ->where('checks.0.type', 'connector')
            ->where('checks.0.status', 'degraded')
            ->where('checks.0.latencyMs', 500)
            ->where('checks.0.message', 'Latency 500 ms exceeded the 200 ms threshold.')
            ->where('checks.0.checkedAt', $check->checked_at->toISOString()));
});

test('administrators resolve an open alert exactly once', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    $alert = MonitorAlert::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.monitoring.alerts.resolve', $alert))
        ->assertRedirect(route('admin.monitoring.dashboard'));

    $resolvedAt = $alert->refresh()->resolved_at;

    expect($resolvedAt)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.monitoring.alerts.resolve', $alert))
        ->assertRedirect(route('admin.monitoring.dashboard'));

    expect($alert->refresh()->resolved_at->toISOString())->toBe($resolvedAt->toISOString());
});

test('disabling the module makes the monitoring routes 404', function () {
    $admin = monitoringUserWithRole(User::ROLE_ADMIN);
    Module::query()->where('slug', 'monitoring')->update(['enabled' => false]);

    $this->actingAs($admin)->get(route('admin.monitoring.dashboard'))->assertNotFound();
    $this->actingAs($admin)->get(route('admin.monitoring.history'))->assertNotFound();
    $this->actingAs($admin)->post(route('admin.monitoring.monitors.store'), monitorPayload())->assertNotFound();
});
