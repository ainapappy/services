<?php

namespace App\Domain\Connectors\Actions;

use App\Domain\Connectors\ConnectorManager;
use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Events\ConnectorStatusChanged;
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
     * A real status transition fires the ConnectorStatusChanged event;
     * retesting an unchanged connector stays silent.
     */
    public function handle(Connector $connector): ConnectorResult
    {
        $result = $this->manager->test($connector);
        $oldStatus = $connector->status;

        $connector->forceFill([
            'status' => $result->status,
            'last_tested_at' => now(),
            'last_error' => $result->success ? null : $result->message,
        ])->save();

        if ($result->status !== $oldStatus) {
            ConnectorStatusChanged::dispatch($connector, $oldStatus, $result->status);
        }

        return $result;
    }
}
