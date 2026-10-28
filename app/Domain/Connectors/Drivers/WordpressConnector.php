<?php

namespace App\Domain\Connectors\Drivers;

/**
 * WordPress driver: the REST root (/wp-json/) is the health endpoint. A
 * 2xx on it proves the REST API answers, without any authentication and
 * without fetching any business data.
 */
class WordpressConnector extends BaseConnector
{
    /**
     * Expose the WordPress REST root as the health endpoint.
     */
    protected function healthPath(): string
    {
        return '/wp-json/';
    }
}
