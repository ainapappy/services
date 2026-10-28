<?php

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Data\CreatedWebhookEndpoint;
use App\Domain\Webhooks\Models\WebhookEndpoint;

class CreateWebhookEndpoint
{
    /**
     * Create an endpoint from validated back-office data.
     *
     * A 64-character hexadecimal secret is generated and stored
     * encrypted: it must stay retrievable to sign the outgoing payloads.
     * It is handed back exactly once and can never be displayed again.
     *
     * @param  list<string>  $events
     */
    public function handle(string $url, array $events, bool $active): CreatedWebhookEndpoint
    {
        $secret = WebhookEndpoint::generateSecret();

        $endpoint = WebhookEndpoint::query()->create([
            'url' => $url,
            'secret' => $secret,
            'events' => $events,
            'active' => $active,
        ]);

        return new CreatedWebhookEndpoint($endpoint, $secret);
    }
}
