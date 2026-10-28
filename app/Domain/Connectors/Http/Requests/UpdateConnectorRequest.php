<?php

namespace App\Domain\Connectors\Http\Requests;

use App\Http\Requests\Concerns\NarrowsValidatedPayload;

/**
 * Validation of the connector edition payload: every field is optional,
 * an omitted field (or an empty credential field) means "keep the stored
 * value".
 */
class UpdateConnectorRequest extends ConnectorRequest
{
    use NarrowsValidatedPayload;

    /**
     * The request updates an existing connector.
     */
    protected function creates(): bool
    {
        return false;
    }

    /**
     * Get the validated data with the precise shape documented by the
     * rules above: every field is re-checked while building the array.
     *
     * @return ($key is null ? array{
     *     name?: string,
     *     type?: string,
     *     base_url?: string,
     *     credentials?: array<string, mixed>
     * } : mixed)
     */
    public function validated($key = null, $default = null)
    {
        if ($key !== null || $default !== null) {
            return parent::validated($key, $default);
        }

        $payload = $this->validatedPayload();

        $data = [];

        $name = $payload['name'] ?? null;

        if (is_string($name)) {
            $data['name'] = $name;
        }

        $type = $payload['type'] ?? null;

        if (is_string($type)) {
            $data['type'] = $type;
        }

        $baseUrl = $payload['base_url'] ?? null;

        if (is_string($baseUrl)) {
            $data['base_url'] = $baseUrl;
        }

        $credentials = $payload['credentials'] ?? null;

        if (is_array($credentials)) {
            $data['credentials'] = $credentials;
        }

        return $data;
    }
}
