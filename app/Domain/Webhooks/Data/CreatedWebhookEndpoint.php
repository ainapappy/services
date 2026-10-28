<?php

namespace App\Domain\Webhooks\Data;

use App\Domain\Webhooks\Models\WebhookEndpoint;

/**
 * The result of an endpoint creation: the persisted endpoint plus the
 * plaintext secret. The secret must be handed to the caller exactly once
 * (flashed); the stored copy stays encrypted, retrievable for signing
 * only.
 */
final class CreatedWebhookEndpoint
{
    /**
     * Create the creation result.
     */
    public function __construct(
        public readonly WebhookEndpoint $endpoint,
        public readonly string $secret,
    ) {}
}
