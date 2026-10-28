<?php

namespace App\Domain\Monitoring\Actions;

use App\Domain\Monitoring\Models\Monitor;

class CreateMonitor
{
    /**
     * Create a monitor. The target is normalized by type: only a
     * connector monitor carries {"connectorId": ...}; a target given
     * for the gateway or webhook types is discarded, since those
     * monitors watch fixed platform targets.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Monitor
    {
        $type = $attributes['type'] ?? null;
        $target = $attributes['target'] ?? null;

        return Monitor::query()->create([
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'target' => Monitor::normalizeTarget(
                is_string($type) ? $type : '',
                is_array($target) ? $target : null,
            ),
            'latency_threshold_ms' => $attributes['latency_threshold_ms'] ?? null,
            'active' => $attributes['active'],
        ]);
    }
}
