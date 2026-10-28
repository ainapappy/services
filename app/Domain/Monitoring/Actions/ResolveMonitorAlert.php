<?php

namespace App\Domain\Monitoring\Actions;

use App\Domain\Monitoring\Models\MonitorAlert;
use Illuminate\Support\Facades\Log;

class ResolveMonitorAlert
{
    /**
     * Resolve a monitor alert, idempotently: an already resolved alert
     * keeps its original resolution date.
     */
    public function handle(MonitorAlert $alert): MonitorAlert
    {
        if (! $alert->isOpen()) {
            return $alert;
        }

        $alert->forceFill(['resolved_at' => now()])->save();

        Log::info(sprintf('Monitoring: alert #%d resolved manually.', $alert->id));

        return $alert;
    }
}
