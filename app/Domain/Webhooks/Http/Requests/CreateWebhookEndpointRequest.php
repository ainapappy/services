<?php

namespace App\Domain\Webhooks\Http\Requests;

use App\Http\Requests\Concerns\NarrowsValidatedPayload;

/**
 * Validation of the endpoint creation payload: everything is required.
 */
class CreateWebhookEndpointRequest extends WebhookEndpointRequest
{
    use NarrowsValidatedPayload;

    /**
     * The request creates an endpoint.
     */
    protected function creates(): bool
    {
        return true;
    }

    /**
     * Get the validated data with the precise shape documented by the
     * rules above: every field is re-checked while building the array.
     *
     * @return ($key is null ? array{url: string, events: list<string>, active: bool} : mixed)
     */
    public function validated($key = null, $default = null)
    {
        if ($key !== null || $default !== null) {
            return parent::validated($key, $default);
        }

        $payload = $this->validatedPayload();

        return [
            'url' => $this->stringField($payload, 'url'),
            'events' => collect($this->arrayField($payload, 'events'))
                ->map(fn (mixed $event): string => is_string($event) ? $event : '')
                ->values()
                ->all(),
            'active' => $this->boolField($payload, 'active', false) ?? false,
        ];
    }
}
