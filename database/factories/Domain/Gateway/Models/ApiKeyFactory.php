<?php

namespace Database\Factories\Domain\Gateway\Models;

use App\Domain\Gateway\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ApiKey>
     */
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     *
     * Keys are created active, with no ability and no per-key rate limit
     * override. The random plaintext secret of the definition is discarded:
     * use "forSecret" when the plaintext secret must be known to the test.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $secret = ApiKey::generateSecret();

        return [
            'name' => fake()->unique()->words(2, true),
            'prefix' => ApiKey::prefixFor($secret),
            'key_hash' => ApiKey::hashSecret($secret),
            'abilities' => [],
            'rate_limit' => null,
            'last_used_at' => null,
            'revoked_at' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Pin the plaintext secret of the key, so tests can authenticate with it.
     */
    public function forSecret(string $secret): static
    {
        return $this->state(fn (): array => [
            'prefix' => ApiKey::prefixFor($secret),
            'key_hash' => ApiKey::hashSecret($secret),
        ]);
    }

    /**
     * Indicate that the key has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * Indicate that the key has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
