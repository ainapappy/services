<?php

namespace App\Domain\Connectors\Http\Requests;

/**
 * Validation of the connector edition payload: every field is optional,
 * an omitted field (or an empty credential field) means "keep the stored
 * value".
 */
class UpdateConnectorRequest extends ConnectorRequest
{
    /**
     * The request updates an existing connector.
     */
    protected function creates(): bool
    {
        return false;
    }
}
