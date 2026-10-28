<?php

namespace App\Domain\Webhooks\Events;

use App\Domain\Webhooks\Models\WebhookDelivery;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a delivery exhausts its retry campaign without ever
 * receiving a 2xx answer: the monitoring layer consumes it in phase 08
 * to alert, then to email. The carried delivery is the persisted failed
 * row.
 */
final class WebhookDeliveryFailed
{
    use Dispatchable;

    /**
     * Create the event.
     */
    public function __construct(
        public readonly WebhookDelivery $delivery,
    ) {}
}
