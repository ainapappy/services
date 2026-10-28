<?php

namespace App\Domain\Webhooks\Listeners;

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use App\Domain\Webhooks\WebhookEvent;

/**
 * Fan a domain event out to every active endpoint subscribed to it: one
 * pending delivery per subscriber, then one delivery job each.
 *
 * Inactive or unsubscribed endpoints receive nothing, and a domain event
 * without a webhook mapping is ignored. The payload envelope is
 * snapshotted at fan-out time, so a delivery always carries the state as
 * it was when the event fired, whatever the queue latency.
 */
final class WebhookDispatcher
{
    /**
     * Handle the incoming domain event.
     */
    public function handle(object $event): void
    {
        $webhookEvent = WebhookEvent::forDomainEvent($event);

        if ($webhookEvent === null) {
            return;
        }

        $payload = [
            'event' => $webhookEvent->value,
            'occurred_at' => now()->toISOString(),
            'data' => $webhookEvent->data($event),
        ];

        $subscribers = WebhookEndpoint::query()
            ->where('active', true)
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint): bool => $endpoint->subscribesTo($webhookEvent));

        foreach ($subscribers as $endpoint) {
            $delivery = $endpoint->deliveries()->create([
                'event' => $webhookEvent->value,
                'payload' => $payload,
                'status' => WebhookDelivery::STATUS_PENDING,
                'attempts' => 0,
            ]);

            DeliverWebhookJob::dispatchSafely($delivery);
        }
    }
}
