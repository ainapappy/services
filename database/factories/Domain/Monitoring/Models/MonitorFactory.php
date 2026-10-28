<?php

namespace Database\Factories\Domain\Monitoring\Models;

use App\Domain\Connectors\Models\Connector;
use App\Domain\Monitoring\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Monitor>
     */
    protected $model = Monitor::class;

    /**
     * Define the model's default state.
     *
     * Monitors are created active, of the "connector" type, without a
     * latency threshold (never degraded) and pointed at a freshly
     * created connector. Use the per-type states for the fixed targets.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => Monitor::TYPE_CONNECTOR,
            'target' => ['connectorId' => Connector::factory()],
            'latency_threshold_ms' => null,
            'active' => true,
        ];
    }

    /**
     * Point the monitor at a specific connector row.
     */
    public function forConnector(Connector $connector): static
    {
        return $this->state(fn (): array => [
            'type' => Monitor::TYPE_CONNECTOR,
            'target' => ['connectorId' => $connector->id],
        ]);
    }

    /**
     * Indicate the monitor probes the gateway health endpoint.
     */
    public function gateway(): static
    {
        return $this->state(fn (): array => [
            'type' => Monitor::TYPE_GATEWAY,
            'target' => [],
        ]);
    }

    /**
     * Indicate the monitor watches the recent failed webhook deliveries.
     */
    public function webhook(): static
    {
        return $this->state(fn (): array => [
            'type' => Monitor::TYPE_WEBHOOK,
            'target' => [],
        ]);
    }

    /**
     * Give the monitor a latency threshold, in milliseconds.
     */
    public function threshold(int $latencyThresholdMs): static
    {
        return $this->state(fn (): array => [
            'latency_threshold_ms' => $latencyThresholdMs,
        ]);
    }

    /**
     * Indicate the monitor is paused and never checked.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
