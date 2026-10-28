<?php

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\WebhookDelivery;

class RetryDelivery
{
    /**
     * Relaunch a failed delivery as a brand-new campaign: the counter
     * restarts from zero, the status goes back to pending, and the last
     * outcome (response code, error) stays on the row until the new
     * attempt overwrites it.
     *
     * Only failed deliveries are retriable: a pending or successful one
     * is returned untouched, so a retry can never start a second
     * campaign in parallel.
     */
    public function handle(WebhookDelivery $delivery): WebhookDelivery
    {
        if (! $delivery->isFailed()) {
            return $delivery;
        }

        $delivery->forceFill([
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempts' => 0,
        ])->save();

        DeliverWebhookJob::dispatchSafely($delivery);

        return $delivery;
    }
}
