<?php

namespace App\Domain\Connectors\Actions;

use App\Domain\Connectors\ConnectorManager;
use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Models\Connector;

class TestConnector
{
    /**
     * Create the action.
     */
    public function __construct(
        private readonly ConnectorManager $manager,
    ) {}

    /**
     * Run the connection test and persist its outcome.
     *
     * Only safe data is stored: the status, the timestamp and a short
     * error message carrying no secret, no payload and no stack trace.
     */
    public function handle(Connector $connector): ConnectorResult
    {
        $result = $this->manager->test($connector);

        $connector->forceFill([
            'status' => $result->status,
            'last_tested_at' => now(),
            'last_error' => $result->success ? null : $result->message,
        ])->save();

        return $result;
    }
}
