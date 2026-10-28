<?php

namespace Database\Factories\Domain\Monitoring\Models;

use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorCheck;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<MonitorCheck>
 */
class MonitorCheckFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<MonitorCheck>
     */
    protected $model = MonitorCheck::class;

    /**
     * Define the model's default state.
     *
     * Checks are created up, with a measured latency, at the current
     * moment, attached to a freshly created monitor unless one is given
     * (see "for").
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'status' => MonitorCheck::STATUS_UP,
            'latency_ms' => 120,
            'message' => null,
            'checked_at' => now(),
        ];
    }

    /**
     * Indicate the target answered unhealthy: no latency, a safe
     * message.
     */
    public function down(): static
    {
        return $this->state(fn (): array => [
            'status' => MonitorCheck::STATUS_DOWN,
            'latency_ms' => null,
            'message' => 'HTTP 500 response.',
        ]);
    }

    /**
     * Indicate the target answered slower than its threshold.
     */
    public function degraded(): static
    {
        return $this->state(fn (): array => [
            'status' => MonitorCheck::STATUS_DEGRADED,
            'latency_ms' => 500,
            'message' => 'Latency 500 ms exceeded the 200 ms threshold.',
        ]);
    }

    /**
     * Pin the moment the check ran.
     */
    public function checkedAt(Carbon $checkedAt): static
    {
        return $this->state(fn (): array => [
            'checked_at' => $checkedAt,
        ]);
    }
}
