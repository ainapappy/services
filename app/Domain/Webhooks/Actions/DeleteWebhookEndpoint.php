<?php

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Models\WebhookEndpoint;

class DeleteWebhookEndpoint
{
    /**
     * Delete an endpoint. Its deliveries are destroyed with it, by the
     * cascade of the foreign key: there is no orphan journal.
     */
    public function handle(WebhookEndpoint $endpoint): void
    {
        $endpoint->delete();
    }
}
