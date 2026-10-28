<?php

use App\Domain\Connectors\ConnectorManager;
use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Models\Connector;
use App\Domain\Connectors\Models\Connector as ConnectorModel;
use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorAlert;
use App\Domain\Monitoring\Models\MonitorCheck;
use App\Domain\Monitoring\Notifications\MonitoringAlert;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/**
 * The command itself is not routed through the module middleware: the
 * scheduler runs it whatever the module state, so no ModuleSeeder here.
 *
 * Connector monitors are driven through a stubbed ConnectorManager
 * instead of faked HTTP: the stub carries a deterministic latency, so
 * the threshold and the transitions never depend on the real timing of
 * a faked request.
 */

/**
 * Bind a ConnectorManager whose test outcome is fully controlled: the
 * success flag, the measured latency and the safe failure message.
 */
function stubConnectorManager(bool $success, int $latencyMs): void
{
    app()->instance(ConnectorManager::class, new class($success, $latencyMs) extends ConnectorManager
    {
        public function __construct(
            private readonly bool $outcome,
            private readonly int $latencyMs,
        ) {
            parent::__construct();
        }

        public function test(ConnectorModel $connector): ConnectorResult
        {
            return new ConnectorResult(
                $this->outcome,
                $this->outcome ? ConnectorModel::STATUS_CONNECTED : ConnectorModel::STATUS_ERROR,
                $this->latencyMs,
                $this->outcome ? null : 'HTTP 500 response.',
            );
        }
    });
}

/**
 * The latest check of a monitor, read fresh: the "latestCheck" relation
 * memoizes on the instance, so the journal query is used instead.
 */
function latestCheckFor(Monitor $monitor): MonitorCheck
{
    return $monitor->checks()->first();
}

test('monitoring:run journals one check per active monitor', function () {
    Http::fake();

    $connectorMonitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();
    $gatewayMonitor = Monitor::factory()->gateway()->create();
    $webhookMonitor = Monitor::factory()->webhook()->create();
    $inactiveMonitor = Monitor::factory()->gateway()->inactive()->create();

    $this->artisan('monitoring:run')->assertSuccessful();

    expect(MonitorCheck::query()->count())->toBe(3)
        ->and(latestCheckFor($connectorMonitor)->status)->toBe(MonitorCheck::STATUS_UP)
        ->and(latestCheckFor($connectorMonitor)->latency_ms)->toBeInt()
        ->and(latestCheckFor($connectorMonitor)->checked_at)->not->toBeNull()
        ->and(latestCheckFor($gatewayMonitor)->status)->toBe(MonitorCheck::STATUS_UP)
        ->and(latestCheckFor($webhookMonitor)->status)->toBe(MonitorCheck::STATUS_UP)
        ->and(latestCheckFor($webhookMonitor)->latency_ms)->toBeNull()
        ->and($inactiveMonitor->checks()->count())->toBe(0);
});

test('a latency above the threshold marks the check degraded and opens a warning', function () {
    Notification::fake();
    User::factory()->admin()->create();

    $monitor = Monitor::factory()
        ->forConnector(Connector::factory()->create())
        ->threshold(10)
        ->create();

    stubConnectorManager(true, 500);

    $this->artisan('monitoring:run')->assertSuccessful();

    $check = latestCheckFor($monitor);

    expect($check->status)->toBe(MonitorCheck::STATUS_DEGRADED)
        ->and($check->latency_ms)->toBe(500)
        ->and($check->message)->toContain('threshold');

    expect(MonitorAlert::query()->sole()->level)->toBe(MonitorAlert::LEVEL_WARNING);
});

test('a monitor without a threshold never degrades', function () {
    $monitor = Monitor::factory()
        ->forConnector(Connector::factory()->create())
        ->create();

    stubConnectorManager(true, 500);

    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_UP)
        ->and(MonitorAlert::query()->count())->toBe(0);
});

test('a transition up to down opens one critical alert and notifies the verified administrators once', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $unverifiedAdmin = User::factory()->unverified()->admin()->create();
    $editor = User::factory()->create();
    $monitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();

    stubConnectorManager(true, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_UP)
        ->and(MonitorAlert::query()->count())->toBe(0);

    stubConnectorManager(false, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    $alert = MonitorAlert::query()->sole();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_DOWN)
        ->and($alert->level)->toBe(MonitorAlert::LEVEL_CRITICAL)
        ->and($alert->isOpen())->toBeTrue()
        ->and($alert->message)->toBe('HTTP 500 response.');

    Notification::assertSentTo($admin, MonitoringAlert::class, 1);
    Notification::assertSentTo(
        $admin,
        MonitoringAlert::class,
        fn (MonitoringAlert $notification): bool => $notification->monitor->is($monitor)
            && $notification->check->status === MonitorCheck::STATUS_DOWN
            && $notification->alert->is($alert),
    );
    Notification::assertNotSentTo($unverifiedAdmin, MonitoringAlert::class);
    Notification::assertNotSentTo($editor, MonitoringAlert::class);
});

test('the first check of an already down monitor opens the alert and notifies too', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $monitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();

    stubConnectorManager(false, 5);

    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_DOWN)
        ->and(MonitorAlert::query()->sole()->level)->toBe(MonitorAlert::LEVEL_CRITICAL);

    Notification::assertSentTo($admin, MonitoringAlert::class, 1);
});

test('returning up resolves the open alerts and never mails the resolution', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $monitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();

    stubConnectorManager(false, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    stubConnectorManager(true, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_UP)
        ->and(MonitorAlert::query()->sole()->isOpen())->toBeFalse();

    Notification::assertSentToTimes($admin, MonitoringAlert::class, 1);
});

test('an unchanged state never repeats the alert nor the notification', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $monitor = Monitor::factory()->forConnector(Connector::factory()->create())->create();

    stubConnectorManager(false, 5);

    $this->artisan('monitoring:run')->assertSuccessful();
    $this->artisan('monitoring:run')->assertSuccessful();
    $this->artisan('monitoring:run')->assertSuccessful();

    expect(MonitorCheck::query()->where('monitor_id', $monitor->id)->count())->toBe(3)
        ->and(MonitorAlert::query()->count())->toBe(1);

    Notification::assertSentToTimes($admin, MonitoringAlert::class, 1);
});

test('down to degraded resolves the critical alert without opening a warning or mailing', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $monitor = Monitor::factory()
        ->forConnector(Connector::factory()->create())
        ->threshold(100)
        ->create();

    stubConnectorManager(true, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    stubConnectorManager(false, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    stubConnectorManager(true, 500);
    $this->artisan('monitoring:run')->assertSuccessful();

    $alert = MonitorAlert::query()->sole();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_DEGRADED)
        ->and($alert->level)->toBe(MonitorAlert::LEVEL_CRITICAL)
        ->and($alert->isOpen())->toBeFalse();

    Notification::assertSentToTimes($admin, MonitoringAlert::class, 1);
});

test('degraded to down opens the critical alert alongside the open warning', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $monitor = Monitor::factory()
        ->forConnector(Connector::factory()->create())
        ->threshold(100)
        ->create();

    stubConnectorManager(true, 500);
    $this->artisan('monitoring:run')->assertSuccessful();

    stubConnectorManager(false, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_DOWN)
        ->and(MonitorAlert::query()->where('level', MonitorAlert::LEVEL_WARNING)->whereNull('resolved_at')->exists())->toBeTrue()
        ->and(MonitorAlert::query()->where('level', MonitorAlert::LEVEL_CRITICAL)->whereNull('resolved_at')->exists())->toBeTrue()
        ->and(MonitorAlert::query()->count())->toBe(2);

    Notification::assertSentToTimes($admin, MonitoringAlert::class, 2);
});

test('a connector monitor whose target disappeared checks down safely', function () {
    Notification::fake();
    User::factory()->admin()->create();

    $monitor = Monitor::factory()->create(['target' => ['connectorId' => 99999]]);

    $this->artisan('monitoring:run')->assertSuccessful();

    $check = latestCheckFor($monitor);

    expect($check->status)->toBe(MonitorCheck::STATUS_DOWN)
        ->and($check->message)->toBe('The target connector no longer exists.')
        ->and(MonitorAlert::query()->where('monitor_id', $monitor->id)->sole()->level)
        ->toBe(MonitorAlert::LEVEL_CRITICAL);
});

test('the gateway monitor probes the internal health endpoint without a key', function () {
    $statusCode = 200;

    Http::fake(function () use (&$statusCode) {
        return Http::response(null, $statusCode);
    });

    $monitor = Monitor::factory()->gateway()->create();

    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_UP);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/health')
        && ! $request->hasHeader('Authorization'));

    $statusCode = 503;

    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_DOWN)
        ->and(latestCheckFor($monitor)->message)->toBe('HTTP 503 response.');
});

test('a webhook monitor goes down while failed deliveries are recent', function () {
    $monitor = Monitor::factory()->webhook()->create();

    WebhookDelivery::factory()->failed()->create(['created_at' => now()->subMinutes(5)]);

    $this->artisan('monitoring:run')->assertSuccessful();

    $check = latestCheckFor($monitor);

    expect($check->status)->toBe(MonitorCheck::STATUS_DOWN)
        ->and($check->latency_ms)->toBeNull()
        ->and($check->message)->toContain('failed deliveries');
});

test('a webhook monitor stays up when the failures are outside the window', function () {
    $monitor = Monitor::factory()->webhook()->create();

    WebhookDelivery::factory()->failed()->create(['created_at' => now()->subMinutes(16)]);

    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_UP);
});

test('a webhook monitor ignores successful deliveries', function () {
    $monitor = Monitor::factory()->webhook()->create();

    WebhookDelivery::factory()->successful()->create(['created_at' => now()->subMinutes(5)]);

    $this->artisan('monitoring:run')->assertSuccessful();

    expect(latestCheckFor($monitor)->status)->toBe(MonitorCheck::STATUS_UP);
});

test('the alert mail uses the project skeleton with the monitor name and an explicit subject', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    Monitor::factory()->create(['name' => 'Blog monitor']);

    stubConnectorManager(false, 5);
    $this->artisan('monitoring:run')->assertSuccessful();

    Notification::assertSentTo(
        $admin,
        MonitoringAlert::class,
        function (MonitoringAlert $notification) use ($admin): bool {
            $mail = $notification->toMail($admin);

            expect($mail->subject)->toBe('[Monitoring] En panne : Blog monitor')
                ->and($mail->view)->toBe('mail.monitoring-alert');

            $html = view($mail->view, $mail->viewData)->render();

            expect($html)->toContain('Blog monitor')
                ->toContain('En panne')
                ->toContain('Critique')
                ->toContain('/admin/monitoring')
                ->toBeEmojiFree();

            return true;
        },
    );
});
