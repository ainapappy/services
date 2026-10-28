<?php

namespace App\Domain\Monitoring\Http\Requests;

class CreateMonitorRequest extends MonitorRequest
{
    /**
     * Whether the request creates a monitor (store route).
     */
    protected function creates(): bool
    {
        return true;
    }
}
