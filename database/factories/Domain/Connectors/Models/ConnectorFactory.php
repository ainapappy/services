<?php

namespace Database\Factories\Domain\Connectors\Models;

use App\Domain\Connectors\Models\Connector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Connector>
 */
class ConnectorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Connector>
     */
    protected $model = Connector::class;

    /**
     * Define the model's default state.
     *
     * Connectors are created untested, with a custom-shaped free-form
     * credential payload. The payload is stored encrypted by the model
     * cast; use the per-type states for a coherent set of attributes.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => Connector::TYPE_CUSTOM,
            'base_url' => 'https://'.fake()->unique()->domainWord().'.test',
            'credentials' => ['token' => bin2hex(random_bytes(16))],
            'status' => Connector::STATUS_UNKNOWN,
            'last_tested_at' => null,
            'last_error' => null,
        ];
    }

    /**
     * Indicate the connector is a WordPress site.
     */
    public function wordpress(): static
    {
        return $this->state(fn (): array => [
            'type' => Connector::TYPE_WORDPRESS,
            'base_url' => 'https://wordpress.test',
            'credentials' => [
                'username' => 'wp-user',
                'password' => bin2hex(random_bytes(16)),
            ],
        ]);
    }

    /**
     * Indicate the connector is a Caren account.
     */
    public function caren(): static
    {
        return $this->state(fn (): array => [
            'type' => Connector::TYPE_CAREN,
            'base_url' => 'https://caren.test',
            'credentials' => [
                'api_key' => bin2hex(random_bytes(12)),
                'secret' => bin2hex(random_bytes(16)),
            ],
        ]);
    }

    /**
     * Indicate the connector is an Erpen account.
     */
    public function erpen(): static
    {
        return $this->state(fn (): array => [
            'type' => Connector::TYPE_ERPEN,
            'base_url' => 'https://erpen.test',
            'credentials' => [
                'api_key' => bin2hex(random_bytes(12)),
                'secret' => bin2hex(random_bytes(16)),
            ],
        ]);
    }

    /**
     * Indicate the connector is a custom service.
     */
    public function custom(): static
    {
        return $this->state(fn (): array => [
            'type' => Connector::TYPE_CUSTOM,
            'base_url' => 'https://custom.test',
            'credentials' => ['token' => bin2hex(random_bytes(16))],
        ]);
    }

    /**
     * Indicate the last connection test succeeded.
     */
    public function connected(): static
    {
        return $this->state(fn (): array => [
            'status' => Connector::STATUS_CONNECTED,
            'last_tested_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Indicate the last connection test failed, with a safe error message.
     */
    public function errored(): static
    {
        return $this->state(fn (): array => [
            'status' => Connector::STATUS_ERROR,
            'last_tested_at' => now(),
            'last_error' => 'HTTP 500 response.',
        ]);
    }
}
