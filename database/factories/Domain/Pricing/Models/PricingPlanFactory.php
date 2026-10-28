<?php

namespace Database\Factories\Domain\Pricing\Models;

use App\Domain\Pricing\Models\PricingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingPlan>
 */
class PricingPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PricingPlan>
     */
    protected $model = PricingPlan::class;

    /**
     * Define the model's default state.
     *
     * Plans are created inactive and not popular by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->words(2, true),
            'tagline' => fake()->sentence(6),
            'price_monthly' => 2900,
            'price_yearly' => 29000,
            'currency' => 'EUR',
            'features' => [
                'Feature one',
                'Feature two',
                'Feature three',
            ],
            'popular' => false,
            'active' => false,
            'sort' => 0,
        ];
    }

    /**
     * Indicate that the plan is active.
     */
    public function active(): static
    {
        return $this->state(fn (): array => ['active' => true]);
    }

    /**
     * Indicate that the plan is highlighted as popular.
     */
    public function popular(): static
    {
        return $this->state(fn (): array => ['popular' => true]);
    }
}
