<?php

namespace App\Domain\Monitoring\Http\Controllers\Admin;

use App\Domain\Connectors\Models\Connector;
use App\Domain\Monitoring\Actions\CreateMonitor;
use App\Domain\Monitoring\Actions\DeleteMonitor;
use App\Domain\Monitoring\Actions\UpdateMonitor;
use App\Domain\Monitoring\Http\Requests\CreateMonitorRequest;
use App\Domain\Monitoring\Http\Requests\UpdateMonitorRequest;
use App\Domain\Monitoring\Models\Monitor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MonitorController extends Controller
{
    /**
     * Display the monitors list, with the current state of each one
     * (the latest check).
     */
    public function index(): Response
    {
        $monitors = Monitor::query()
            ->with('latestCheck')
            ->orderBy('name')
            ->get()
            ->map(fn (Monitor $monitor): array => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'type' => $monitor->type,
                'active' => $monitor->active,
                'latencyThresholdMs' => $monitor->latency_threshold_ms,
                'targetSummary' => $monitor->targetSummary(),
                'lastStatus' => $monitor->latestCheck?->status,
                'lastCheckedAt' => $monitor->latestCheck?->checked_at?->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Monitoring/Monitors', ['monitors' => $monitors]);
    }

    /**
     * Display the monitor creation form.
     */
    public function create(): Response
    {
        return Inertia::render('back/Monitoring/MonitorForm', [
            'connectors' => $this->connectors(),
        ]);
    }

    /**
     * Store a new monitor: the action normalizes the target by type.
     */
    public function store(CreateMonitorRequest $request, CreateMonitor $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('admin.monitoring.monitors.index');
    }

    /**
     * Display the monitor edition form.
     */
    public function edit(Monitor $monitor): Response
    {
        return Inertia::render('back/Monitoring/MonitorForm', [
            'monitor' => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'type' => $monitor->type,
                'active' => $monitor->active,
                'latencyThresholdMs' => $monitor->latency_threshold_ms,
                'target' => $monitor->target,
            ],
            'connectors' => $this->connectors(),
        ]);
    }

    /**
     * Update a monitor: an omitted field keeps its stored value.
     */
    public function update(UpdateMonitorRequest $request, Monitor $monitor, UpdateMonitor $action): RedirectResponse
    {
        $action->handle($monitor, $request->validated());

        return redirect()->route('admin.monitoring.monitors.index');
    }

    /**
     * Delete a monitor and its journal and alerts with it.
     */
    public function destroy(Monitor $monitor, DeleteMonitor $action): RedirectResponse
    {
        $action->handle($monitor);

        return redirect()->route('admin.monitoring.monitors.index');
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
