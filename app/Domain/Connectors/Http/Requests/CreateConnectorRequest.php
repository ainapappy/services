<?php

namespace App\Domain\Connectors\Http\Requests;

use App\Http\Requests\Concerns\NarrowsValidatedPayload;

/**
 * Validation of the connector creation payload: everything is required,
 * including the whole credentials object of the target type.
 */
class CreateConnectorRequest extends ConnectorRequest
{
    use NarrowsValidatedPayload;

    /**
     * The request creates a connector.
     */
    protected function creates(): bool
    {
        return true;
    }

    /**
     * Get the validated data with the precise shape documented by the
     * rules above: every field is re-checked while building the array.
     *
     * @return ($key is null ? array{
     *     name: string,
     *     type: string,
     *     base_url: string,
     *     credentials: array<string, mixed>
     * } : mixed)
     */
    public function validated($key = null, $default = null)
    {
        if ($key !== null || $default !== null) {
            return parent::validated($key, $default);
        }

        $payload = $this->validatedPayload();

        return [
            'name' => $this->stringField($payload, 'name'),
            'type' => $this->stringField($payload, 'type'),
            'base_url' => $this->stringField($payload, 'base_url'),
            'credentials' => $this->arrayField($payload, 'credentials'),
        ];
    }
}
