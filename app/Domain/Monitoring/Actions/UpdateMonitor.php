<?php

namespace App\Domain\Monitoring\Actions;

use App\Domain\Monitoring\Models\Monitor;

class UpdateMonitor
{
    /**
     * Update a monitor: an omitted field keeps the stored value, a
     * provided one replaces it. The type is the pivot of the target
     * normalization — switching to or from "connector" recomputes the
     * target, staying keeps the previous connector unless the payload
     * names another one.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Monitor $monitor, array $attributes): Monitor
    {
        $fill = collect($attributes)
            ->only(['name', 'type', 'latency_threshold_ms', 'active'])
            ->all();

        if (array_key_exists('type', $attributes) || array_key_exists('target', $attributes)) {
            $type = $attributes['type'] ?? $monitor->type;
            $target = $attributes['target'] ?? null;

            $fill['target'] = Monitor::normalizeTarget(
                is_string($type) ? $type : '',
                is_array($target) ? $target : null,
                $monitor,
            );
        }

        $monitor->fill($fill)->save();

        return $monitor;
    }
}
