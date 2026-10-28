<?php

namespace App\Domain\Connectors\Jobs;

use App\Domain\Connectors\Actions\TestConnector;
use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Models\Connector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Run the connection test of one connector on the queue.
 *
 * Security contract: the job never logs anything, and the model travels
 * serialized with its credentials still encrypted ("credentials" is
 * hidden and encrypted at rest). Failures surface only through the safe
 * "last_error" column.
 */
class TestConnectorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted: a connection test is
     * never retried, the back-office test button answers at once.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create the job.
     */
    public function __construct(
        public readonly Connector $connector,
    ) {}

    /**
     * Execute the job: exactly the same treatment as the synchronous
     * action, so the stored outcome is the same whatever the queue.
     */
    public function handle(TestConnector $action): ConnectorResult
    {
        return $action->handle($this->connector);
    }
}
