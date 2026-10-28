<?php

namespace Database\Factories\Domain\Webhooks\Models;

use App\Domain\Webhooks\Models\WebhookEndpoint;
use App\Domain\Webhooks\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WebhookEndpoint>
     */
    protected $model = WebhookEndpoint::class;

    /**
     * Define the model's default state.
     *
     * Endpoints are created active and subscribed to every canonical
     * event; the random plaintext secret of the definition is stored
     * encrypted by the model cast. Narrow the subscriptions with
     * "subscribedTo" and pause the endpoint with "inactive".
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => 'https://'.fake()->unique()->domainWord().'.test/webhooks',
            'secret' => WebhookEndpoint::generateSecret(),
            'events' => WebhookEvent::values(),
            'active' => true,
        ];
    }

    /**
     * Indicate the endpoint is paused and receives nothing.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }

    /**
     * Narrow the subscription list to exactly the given canonical events.
     */
    public function subscribedTo(string ...$events): static
    {
        return $this->state(fn (): array => [
            'events' => $events === [] ? WebhookEvent::values() : array_values($events),
        ]);
    }
}
