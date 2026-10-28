<?php

namespace App\Domain\Monitoring\Http\Controllers\Admin;

use App\Domain\Connectors\Models\Connector;
use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorAlert;
use App\Domain\Monitoring\Models\MonitorCheck;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    /**
     * The number of checks fed to the sparkline of one monitor.
     */
    final public const SPARKLINE_POINTS = 20;

    /**
     * Display the monitoring dashboard: one card per monitor with its
     * current state (the latest check, eager-loaded through the
     * "latestCheck" relation — no current-state column by design), its
     * open alerts and a chronological sparkline of its last checks.
     */
    public function index(): Response
    {
        $monitors = Monitor::query()
            ->with(['latestCheck', 'openAlerts'])
            ->with(['checks' => fn ($query) => $query
                ->latest('checked_at')
                ->latest('id')
                ->limit(self::SPARKLINE_POINTS)])
            ->orderBy('name')
            ->get()
            ->map(fn (Monitor $monitor): array => $this->card($monitor))
            ->values()
            ->all();

        return Inertia::render('back/Monitoring/Dashboard', [
            'monitors' => $monitors,
            'connectors' => $this->connectors(),
        ]);
    }

    /**
     * The card of one monitor, shaped for the Dashboard page.
     *
     * @return array<string, mixed>
     */
    private function card(Monitor $monitor): array
    {
        $latest = $monitor->latestCheck;

        return [
            'id' => $monitor->id,
            'name' => $monitor->name,
            'type' => $monitor->type,
            'active' => $monitor->active,
            'latencyThresholdMs' => $monitor->latency_threshold_ms,
            'targetSummary' => $monitor->targetSummary(),
            'status' => $latest?->status,
            'latencyMs' => $latest?->latency_ms,
            'checkedAt' => $latest?->checked_at?->toISOString(),
            'message' => $latest?->message,
            'sparkline' => $this->sparkline($monitor),
            'alerts' => $monitor->openAlerts
                ->map(fn (MonitorAlert $alert): array => [
                    'id' => $alert->id,
                    'level' => $alert->level,
                    'message' => $alert->message,
                    'createdAt' => $alert->created_at->toISOString(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * The last checks of the monitor, chronological order, as the
     * latency points of the sparkline (null when the check measured no
     * latency). Reads the eager-loaded relation: one query for all
     * monitors, never one per card.
     *
     * @return list<array{latencyMs: int|null}>
     */
    private function sparkline(Monitor $monitor): array
    {
        return array_values(
            $monitor->checks
                ->reverse()
                ->values()
                ->map(fn (MonitorCheck $check): array => ['latencyMs' => $check->latency_ms])
                ->all()
        );
    }

    /**
     * The connectors eligible as a monitor target.
     *
     * @return list<array{id: int, name: string}>
     */
    private function connectors(): array
    {
        return array_values(
            Connector::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Connector $connector): array => [
                    'id' => $connector->id,
                    'name' => $connector->name,
                ])
                ->all()
        );
    }
}
