<?php

namespace Database\Factories\Domain\Pricing\Models;

use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingTable>
 */
class PricingTableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PricingTable>
     */
    protected $model = PricingTable::class;

    /**
     * Define the model's default state.
     *
     * Tables are created unpublished by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->words(3, true),
            'subtitle' => fake()->sentence(8),
            'faq' => [
                ['question' => 'Question one?', 'answer' => 'Answer one.'],
                ['question' => 'Question two?', 'answer' => 'Answer two.'],
            ],
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the table is published.
     */
    public function published(): static
    {
        return $this->state(fn (): array => ['published_at' => now()]);
    }

    /**
     * Attach active plans to the table, keeping the factory order.
     *
     * @param  list<int>|int  $plans  explicit ids in display order, or a count
     */
    public function withPlans(array|int $plans = 3): static
    {
        return $this->afterCreating(function (PricingTable $table) use ($plans): void {
            $ids = is_array($plans)
                ? $plans
                : PricingPlan::factory()->active()->count($plans)->create()->modelKeys();

            foreach ($ids as $index => $id) {
                $table->plans()->attach($id, ['sort' => $index]);
            }
        });
    }
}
