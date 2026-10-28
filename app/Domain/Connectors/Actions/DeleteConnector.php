<?php

namespace App\Domain\Connectors\Actions;

use App\Domain\Connectors\Models\Connector;

class DeleteConnector
{
    /**
     * Delete a connector. The encrypted credentials are destroyed with
     * the row: there is no history of secrets.
     */
    public function handle(Connector $connector): void
    {
        $connector->delete();
    }
}
