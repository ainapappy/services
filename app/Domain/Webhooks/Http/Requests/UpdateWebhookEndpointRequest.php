<?php

namespace App\Domain\Webhooks\Http\Requests;

use App\Http\Requests\Concerns\NarrowsValidatedPayload;

/**
 * Validation of the endpoint edition payload: every field is optional,
 * an omitted field means "keep the stored value". The secret is not part
 * of the payload, ever.
 */
class UpdateWebhookEndpointRequest extends WebhookEndpointRequest
{
    use NarrowsValidatedPayload;

    /**
     * The request updates an existing endpoint.
     */
    protected function creates(): bool
    {
        return false;
    }

    /**
     * Get the validated data with the precise shape documented by the
     * rules above: every field is re-checked while building the array.
     *
     * @return ($key is null ? array{url?: string, events?: list<string>, active?: bool} : mixed)
     */
    public function validated($key = null, $default = null)
    {
        if ($key !== null || $default !== null) {
            return parent::validated($key, $default);
        }

        $payload = $this->validatedPayload();

        $data = [];

        $url = $payload['url'] ?? null;

        if (is_string($url)) {
            $data['url'] = $url;
        }

        $events = $payload['events'] ?? null;

        if (is_array($events)) {
            $data['events'] = collect($events)
                ->map(fn (mixed $event): string => is_string($event) ? $event : '')
                ->values()
                ->all();
        }

        $active = $payload['active'] ?? null;

        if (is_bool($active)) {
            $data['active'] = $active;
        }

        return $data;
    }
}
