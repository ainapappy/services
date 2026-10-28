<?php

namespace Database\Factories\Domain\Monitoring\Models;

use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorAlert>
 */
class MonitorAlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<MonitorAlert>
     */
    protected $model = MonitorAlert::class;

    /**
     * Define the model's default state.
     *
     * Alerts are created critical and open (resolved_at null), attached
     * to a freshly created monitor unless one is given (see "for").
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'message' => 'HTTP 500 response.',
            'level' => MonitorAlert::LEVEL_CRITICAL,
            'resolved_at' => null,
        ];
    }

    /**
     * Indicate the alert warns about a degraded state.
     */
    public function warning(): static
    {
        return $this->state(fn (): array => [
            'message' => 'Latency 500 ms exceeded the 200 ms threshold.',
            'level' => MonitorAlert::LEVEL_WARNING,
        ]);
    }

    /**
     * Indicate the alert was already resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'resolved_at' => now(),
        ]);
    }
}
