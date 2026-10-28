<?php

namespace App\Domain\Webhooks\Http\Requests;

/**
 * Validation of the endpoint edition payload: every field is optional,
 * an omitted field means "keep the stored value". The secret is not part
 * of the payload, ever.
 */
class UpdateWebhookEndpointRequest extends WebhookEndpointRequest
{
    /**
     * The request updates an existing endpoint.
     */
    protected function creates(): bool
    {
        return false;
    }

    /**
     * Get the validated data with a precise shape, as documented by the
     * rules above.
     *
     * @return array{url?: string, events?: list<string>, active?: bool}
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}
