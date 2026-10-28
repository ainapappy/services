<?php

namespace App\Domain\Monitoring\Actions;

use App\Domain\Monitoring\Models\Monitor;

class DeleteMonitor
{
    /**
     * Delete a monitor: its journal and its alerts die with it
     * (cascade).
     */
    public function handle(Monitor $monitor): void
    {
        $monitor->delete();
    }
}
