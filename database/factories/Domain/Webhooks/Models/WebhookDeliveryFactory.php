<?php

namespace Database\Factories\Domain\Webhooks\Models;

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use App\Domain\Webhooks\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WebhookDelivery>
     */
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     *
     * Deliveries are created pending, never attempted, carrying a
     * landing.published envelope. The delivery is attached to a freshly
     * created endpoint unless one is given (see "for").
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event' => WebhookEvent::LandingPublished->value,
            'payload' => [
                'event' => WebhookEvent::LandingPublished->value,
                'occurred_at' => now()->toISOString(),
                'data' => [
                    'slug' => 'demo',
                    'title' => 'Demo',
                    'heroTitle' => 'Hello there',
                    'heroSubtitle' => 'World',
                    'ctas' => [],
                ],
            ],
        ];
    }

    /**
     * Indicate the delivery reached its endpoint: a 2xx answer, one
     * attempt, measured duration.
     */
    public function successful(): static
    {
        return $this->state(fn (): array => [
            'status' => WebhookDelivery::STATUS_SUCCESS,
            'attempts' => 1,
            'response_code' => 200,
            'duration_ms' => 120,
            'delivered_at' => now(),
            'error' => null,
        ]);
    }

    /**
     * Indicate the retry campaign was exhausted: five attempts, no 2xx.
     */
    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => WebhookDelivery::STATUS_FAILED,
            'attempts' => DeliverWebhookJob::MAX_ATTEMPTS,
            'response_code' => 500,
            'duration_ms' => 120,
            'delivered_at' => null,
            'error' => 'HTTP 500 response.',
        ]);
    }

    /**
     * Pin the canonical event carried by the delivery.
     */
    public function forEvent(WebhookEvent $event): static
    {
        return $this->state(fn (): array => [
            'event' => $event->value,
            'payload' => [
                'event' => $event->value,
                'occurred_at' => now()->toISOString(),
                'data' => [],
            ],
        ]);
    }
}
