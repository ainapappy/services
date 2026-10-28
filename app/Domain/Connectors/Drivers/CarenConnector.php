<?php

namespace App\Domain\Connectors\Drivers;

/**
 * Caren driver: the base URL is the API entry point. A 2xx on it proves
 * the service answers, without any authentication and without fetching
 * any business data.
 */
class CarenConnector extends BaseConnector
{
    /**
     * Probe the base URL directly as the health endpoint.
     */
    protected function healthPath(): string
    {
        return '';
    }
}
