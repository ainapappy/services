<?php

namespace App\Domain\Connectors\Drivers;

/**
 * Erpen driver: the base URL is the API entry point. A 2xx on it proves
 * the service answers, without any authentication and without fetching
 * any business data.
 */
class ErpenConnector extends BaseConnector
{
    /**
     * Probe the base URL directly as the health endpoint.
     */
    protected function healthPath(): string
    {
        return '';
    }
}
