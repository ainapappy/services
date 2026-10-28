<?php

namespace App\Domain\Webhooks\Listeners;

use App\Domain\Webhooks\Events\WebhookDeliveryFailed;
use App\Domain\Webhooks\Notifications\WebhookFailureMail;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Consumes WebhookDeliveryFailed and notifies the verified
 * administrators by mail (log fallback inside the notification): one
 * mail per exhausted delivery campaign, carrying endpoint, event,
 * attempts and the last response code / error.
 *
 * The send is guarded: a mail failure must never break the webhook
 * pipeline — the event fires from inside DeliverWebhookJob, after the
 * delivery row was already persisted as failed.
 */
final class NotifyWebhookFailure
{
    /**
     * Notify the verified administrators of the exhausted campaign.
     */
    public function handle(WebhookDeliveryFailed $event): void
    {
        $recipients = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->whereNotNull('email_verified_at')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, new WebhookFailureMail($event->delivery));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
