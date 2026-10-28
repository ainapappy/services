<?php

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Models\WebhookEndpoint;

class UpdateWebhookEndpoint
{
    /**
     * Update an endpoint with validated back-office data.
     *
     * Omitted fields are left untouched, and the secret is never part of
     * the updatable payload: it only changes through regeneration.
     *
     * @param  array{url?: string, events?: list<string>, active?: bool}  $data
     */
    public function handle(WebhookEndpoint $endpoint, array $data): WebhookEndpoint
    {
        $endpoint->fill([
            'url' => $data['url'] ?? $endpoint->url,
            'events' => $data['events'] ?? $endpoint->events,
            'active' => $data['active'] ?? $endpoint->active,
        ])->save();

        return $endpoint;
    }
}
