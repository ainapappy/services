<?php

namespace Database\Factories\Domain\Landing\Models;

use App\Domain\Landing\Models\LandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingPage>
 */
class LandingPageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<LandingPage>
     */
    protected $model = LandingPage::class;

    /**
     * Define the model's default state.
     *
     * Pages are created unpublished and not flagged as home by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->words(3, true),
            'hero_title' => fake()->sentence(4),
            'hero_subtitle' => fake()->sentence(12),
            'ctas' => [
                ['label' => 'Get started', 'url' => 'https://services.test/register', 'style' => 'primary'],
                ['label' => 'Learn more', 'url' => '/pricing', 'style' => 'secondary'],
            ],
            'seo' => [
                'title' => fake()->sentence(3),
                'description' => fake()->sentence(10),
            ],
            'is_home' => false,
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the page is the home page.
     */
    public function home(): static
    {
        return $this->state(fn (): array => [
            'is_home' => true,
        ]);
    }
}
