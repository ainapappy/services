<?php

namespace App\Domain\Connectors\Drivers;

/**
 * Custom driver: the base URL is probed directly, since a custom service
 * has no known health endpoint. A 2xx proves the service answers.
 */
class CustomConnector extends BaseConnector
{
    /**
     * Probe the base URL directly as the health endpoint.
     */
    protected function healthPath(): string
    {
        return '';
    }
}
