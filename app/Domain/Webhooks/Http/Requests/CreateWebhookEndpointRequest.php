<?php

namespace App\Domain\Webhooks\Http\Requests;

/**
 * Validation of the endpoint creation payload: everything is required.
 */
class CreateWebhookEndpointRequest extends WebhookEndpointRequest
{
    /**
     * The request creates an endpoint.
     */
    protected function creates(): bool
    {
        return true;
    }

    /**
     * Get the validated data with a precise shape, as documented by the
     * rules above.
     *
     * @return array{url: string, events: list<string>, active: bool}
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}
