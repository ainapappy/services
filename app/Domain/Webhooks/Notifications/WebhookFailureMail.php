<?php

namespace App\Domain\Webhooks\Notifications;

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The terminal failure of a webhook delivery campaign: mail is the
 * primary channel, the log channel is the local fallback.
 *
 * The mail renders the project mail skeleton (mail.webhook-failure):
 * endpoint, event, exhausted attempts, last response code and error,
 * with the send icon. French copy, no emoji, no remote image. The
 * recipients are the verified administrators, resolved by the listener.
 */
class WebhookFailureMail extends Notification
{
    /**
     * Create the notification.
     */
    public function __construct(
        public readonly WebhookDelivery $delivery,
    ) {}

    /**
     * Get the delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'log'];
    }

    /**
     * Build the mail message from the project mail skeleton.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.webhook-failure', $this->templateData());
    }

    /**
     * Get the array representation (log channel fallback).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'endpoint_url' => $this->delivery->endpoint?->url,
            'event' => $this->delivery->event,
            'attempts' => $this->delivery->attempts,
            'response_code' => $this->delivery->response_code,
            'error' => $this->delivery->error,
        ];
    }

    /**
     * The explicit, emoji-free subject: the endpoint host when it can be
     * read, the full endpoint otherwise.
     */
    private function subject(): string
    {
        $endpoint = $this->delivery->endpoint;
        $url = $endpoint instanceof WebhookEndpoint ? $endpoint->url : '';

        $host = parse_url($url, PHP_URL_HOST);

        return sprintf(
            '[Webhooks] Échec définitif : %s',
            is_string($host) && $host !== '' ? $host : $url,
        );
    }

    /**
     * The data handed to the Blade view.
     *
     * @return array<string, int|string>
     */
    private function templateData(): array
    {
        $endpoint = $this->delivery->endpoint;

        return [
            'subject' => $this->subject(),
            'endpointUrl' => $endpoint instanceof WebhookEndpoint ? $endpoint->url : '',
            'eventName' => $this->delivery->event,
            'attempts' => $this->delivery->attempts,
            'maxAttempts' => DeliverWebhookJob::MAX_ATTEMPTS,
            'responseCodeLabel' => $this->delivery->response_code !== null
                ? (string) $this->delivery->response_code
                : 'aucune réponse',
            'errorLabel' => $this->delivery->error ?? 'non renseignée',
            'occurredAt' => $this->delivery->updated_at->format('d/m/Y à H:i').' UTC',
            'actionUrl' => route('admin.webhooks.deliveries.show', $this->delivery),
        ];
    }
}
