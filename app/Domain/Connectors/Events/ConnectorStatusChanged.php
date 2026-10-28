<?php

namespace App\Domain\Connectors\Events;

use App\Domain\Connectors\Models\Connector;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a connection test changes the status of a connector
 * (connected, error or unknown). Retesting an unchanged connector never
 * fires it. The Webhooks module listens to it to push the new status to
 * subscribed endpoints.
 */
final class ConnectorStatusChanged
{
    use Dispatchable;

    /**
     * Create the event.
     */
    public function __construct(
        public readonly Connector $connector,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
