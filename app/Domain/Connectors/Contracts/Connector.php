<?php

namespace App\Domain\Connectors\Contracts;

use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Models\Connector as ConnectorModel;

/**
 * A connector driver performs the connection test of one connector type.
 *
 * Implementations stay read-only: a test pings a health endpoint and
 * never fetches business data. The returned messages must be safe by
 * construction — no credential, no request payload, no stack trace.
 */
interface Connector
{
    /**
     * Run the connection test of the given connector.
     */
    public function test(ConnectorModel $connector): ConnectorResult;
}
