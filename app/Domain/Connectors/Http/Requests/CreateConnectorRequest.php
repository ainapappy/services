<?php

namespace App\Domain\Connectors\Http\Requests;

/**
 * Validation of the connector creation payload: everything is required,
 * including the whole credentials object of the target type.
 */
class CreateConnectorRequest extends ConnectorRequest
{
    /**
     * The request creates a connector.
     */
    protected function creates(): bool
    {
        return true;
    }

    /**
     * Get the validated data with a precise shape, as documented by the
     * rules above.
     *
     * @return array{name: string, type: string, base_url: string, credentials: array<string, mixed>}
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}
