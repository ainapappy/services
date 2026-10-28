<?php

namespace App\Domain\Monitoring\Http\Requests;

class UpdateMonitorRequest extends MonitorRequest
{
    /**
     * Whether the request creates a monitor (store route).
     */
    protected function creates(): bool
    {
        return false;
    }
}
