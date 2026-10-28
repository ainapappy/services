<?php

namespace App\Domain\Monitoring\Actions;

use App\Domain\Connectors\ConnectorManager;
use App\Domain\Monitoring\Data\CheckOutcome;
use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorAlert;
use App\Domain\Monitoring\Models\MonitorCheck;
use App\Domain\Monitoring\Notifications\MonitoringAlert;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Run the health checks of every active monitor: one journal entry
 * (monitor_checks) per monitor per run, then an alert reconciliation on
 * the transition the check just recorded.
 *
 * The checks by type:
 * - connector: the ConnectorManager driver test — "connected" is up,
 *   "error" is down, the measured latency travels with the result;
 * - gateway: a plain GET (no API key) on {APP_URL}/api/v1/health — up
 *   on 2xx, down otherwise, latency measured;
 * - webhook: the failed deliveries created within the last fifteen
 *   minutes (WEBHOOK_WINDOW_MINUTES) — up when none, down otherwise,
 *   latency null. The window is a sliding, stateless window on purpose:
 *   it does not depend on the previous check, so a run inspects a
 *   deterministic slice of activity (with the five-minute schedule,
 *   roughly the last three runs), and an old failure that was already
 *   handled never keeps the monitor down forever.
 *
 * A "down" answer keeps its measured latency; the latency threshold
 * only ever turns an "up" answer into "degraded" — a null threshold
 * never degrades.
 */
class RunMonitors
{
    /**
     * The sliding window of the webhook monitor, in minutes.
     */
    final public const WEBHOOK_WINDOW_MINUTES = 15;

    /**
     * The timeout of the gateway probe, in seconds.
     */
    final public const GATEWAY_TIMEOUT_SECONDS = 5;

    /**
     * Create the action.
     */
    public function __construct(
        private readonly ConnectorManager $connectors,
    ) {}

    /**
     * Check every active monitor.
     */
    public function handle(): void
    {
        $monitors = Monitor::query()
            ->where('active', true)
            ->get();

        foreach ($monitors as $monitor) {
            $this->check($monitor);
        }
    }

    /**
     * Run one check and reconcile the alerts with its transition.
     */
    private function check(Monitor $monitor): void
    {
        $previousStatus = $monitor->latestCheck?->status;

        $outcome = $this->applyThreshold($monitor, $this->resolve($monitor));

        $check = $monitor->checks()->create([
            'status' => $outcome->status,
            'latency_ms' => $outcome->latencyMs,
            'message' => $outcome->message,
            'checked_at' => now(),
        ]);

        $this->reconcileAlerts($monitor, $previousStatus, $check);
    }

    /**
     * Resolve the raw outcome of the check, by monitor type.
     */
    private function resolve(Monitor $monitor): CheckOutcome
    {
        return match ($monitor->type) {
            Monitor::TYPE_CONNECTOR => $this->resolveConnector($monitor),
            Monitor::TYPE_GATEWAY => $this->resolveGateway(),
            Monitor::TYPE_WEBHOOK => $this->resolveWebhook(),
            default => throw new InvalidArgumentException("Unsupported monitor type [{$monitor->type}]."),
        };
    }

    /**
     * Test the targeted connector through its type driver. A monitor
     * whose target no longer exists stands up safely: the check is down,
     * with a generic message.
     */
    private function resolveConnector(Monitor $monitor): CheckOutcome
    {
        $connector = $monitor->targetConnector();

        if ($connector === null) {
            return new CheckOutcome(MonitorCheck::STATUS_DOWN, null, 'The target connector no longer exists.');
        }

        $result = $this->connectors->test($connector);

        if (! $result->success) {
            return new CheckOutcome(MonitorCheck::STATUS_DOWN, $result->latencyMs, $result->message);
        }

        return new CheckOutcome(MonitorCheck::STATUS_UP, $result->latencyMs, null);
    }

    /**
     * Probe the internal gateway health endpoint without any API key:
     * the probe must stay possible even when no key exists. A disabled
     * gateway module answers 404 — read as down, which is exactly what
     * the monitor should report.
     */
    private function resolveGateway(): CheckOutcome
    {
        $target = rtrim((string) config('app.url'), '/').'/api/v1/health';
        $startedAt = hrtime(true);

        try {
            $response = Http::timeout(self::GATEWAY_TIMEOUT_SECONDS)->get($target);
        } catch (ConnectionException) {
            // The exception wording may carry host resolution details:
            // replace it with a generic, safe message.
            return new CheckOutcome(MonitorCheck::STATUS_DOWN, $this->latencyMs($startedAt), 'Host unreachable.');
        }

        $latencyMs = $this->latencyMs($startedAt);

        if (! $response->successful()) {
            return new CheckOutcome(MonitorCheck::STATUS_DOWN, $latencyMs, 'HTTP '.$response->status().' response.');
        }

        return new CheckOutcome(MonitorCheck::STATUS_UP, $latencyMs, null);
    }

    /**
     * Count the deliveries whose campaign was exhausted within the
     * sliding window: one recent failure is enough to declare the
     * webhook surface down.
     */
    private function resolveWebhook(): CheckOutcome
    {
        $failures = WebhookDelivery::query()
            ->where('status', WebhookDelivery::STATUS_FAILED)
            ->where('created_at', '>=', now()->subMinutes(self::WEBHOOK_WINDOW_MINUTES))
            ->count();

        if ($failures === 0) {
            return new CheckOutcome(MonitorCheck::STATUS_UP, null, null);
        }

        return new CheckOutcome(
            MonitorCheck::STATUS_DOWN,
            null,
            sprintf('%d failed deliveries in the last %d minutes.', $failures, self::WEBHOOK_WINDOW_MINUTES),
        );
    }

    /**
     * Apply the latency threshold: an "up" answer slower than the
     * threshold becomes "degraded". A null threshold, a "down" answer
     * or a check without a measured latency never degrade.
     */
    private function applyThreshold(Monitor $monitor, CheckOutcome $outcome): CheckOutcome
    {
        $threshold = $monitor->latency_threshold_ms;

        if ($outcome->status !== MonitorCheck::STATUS_UP
            || $threshold === null
            || $outcome->latencyMs === null
            || $outcome->latencyMs <= $threshold
        ) {
            return $outcome;
        }

        return new CheckOutcome(
            MonitorCheck::STATUS_DEGRADED,
            $outcome->latencyMs,
            sprintf('Latency %d ms exceeded the %d ms threshold.', $outcome->latencyMs, $threshold),
        );
    }

    /**
     * Reconcile the alerts with the transition the check just recorded.
     * Anti-spam by construction — an unchanged status never touches the
     * alerts:
     *
     * - a first check, up -> down or degraded -> down opens one critical
     *   alert and notifies the verified administrators once;
     * - a first check or up -> degraded opens one warning alert and
     *   notifies them once;
     * - down -> degraded is a partial recovery: the open critical alerts
     *   are resolved, with no new alert and no mail;
     * - back to "up" resolves every open alert (warnings included),
     *   logged only — no resolution mail.
     *
     * A warning opened before a fall to "down" stays open alongside the
     * critical one: both are resolved by the return to "up".
     */
    private function reconcileAlerts(Monitor $monitor, ?string $previousStatus, MonitorCheck $check): void
    {
        if ($previousStatus === $check->status) {
            return;
        }

        if ($check->status === MonitorCheck::STATUS_UP) {
            $this->resolveOpenAlerts($monitor);

            return;
        }

        if ($check->status === MonitorCheck::STATUS_DEGRADED && $previousStatus === MonitorCheck::STATUS_DOWN) {
            $this->resolveOpenAlerts($monitor, MonitorAlert::LEVEL_CRITICAL);

            return;
        }

        $this->openAlert($monitor, $check);
    }

    /**
     * Open one alert for the check and notify the verified
     * administrators exactly once.
     */
    private function openAlert(Monitor $monitor, MonitorCheck $check): void
    {
        $alert = $monitor->alerts()->create([
            'message' => $check->message ?? sprintf('The monitor is %s.', $check->status),
            'level' => $check->status === MonitorCheck::STATUS_DOWN
                ? MonitorAlert::LEVEL_CRITICAL
                : MonitorAlert::LEVEL_WARNING,
            'resolved_at' => null,
        ]);

        $recipients = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->whereNotNull('email_verified_at')
            ->get();

        Notification::send($recipients, new MonitoringAlert($monitor, $check, $alert));
    }

    /**
     * Resolve the open alerts of the monitor — every level, or only the
     * given one — and log the resolution: no resolution mail.
     */
    private function resolveOpenAlerts(Monitor $monitor, ?string $level = null): void
    {
        $open = $monitor->openAlerts()
            ->when($level !== null, fn (Builder $query): Builder => $query->where('level', $level))
            ->get();

        foreach ($open as $alert) {
            $alert->forceFill(['resolved_at' => now()])->save();
        }

        if ($open->isNotEmpty()) {
            Log::info(sprintf(
                'Monitoring: %d alert(s) resolved for monitor "%s".',
                $open->count(),
                $monitor->name,
            ));
        }
    }

    /**
     * The elapsed milliseconds since the given hrtime tick.
     */
    private function latencyMs(int|float $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1e6);
    }
}
