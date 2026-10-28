<?php

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Models\WebhookEndpoint;

class RegenerateWebhookSecret
{
    /**
     * Replace the secret of an endpoint: the new one is generated,
     * stored encrypted and handed back exactly once, flashed to the
     * back-office. The previous secret stops working immediately: every
     * further delivery is signed with the stored one.
     */
    public function handle(WebhookEndpoint $endpoint): string
    {
        $secret = WebhookEndpoint::generateSecret();

        $endpoint->forceFill(['secret' => $secret])->save();

        return $secret;
    }
}
