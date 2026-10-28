<?php

namespace App\Domain\Gateway\Actions;

use App\Domain\Gateway\Data\CreatedApiKey;
use App\Domain\Gateway\Models\ApiKey;

class CreateApiKey
{
    /**
     * Create an API key from validated back-office data.
     *
     * A high-entropy secret is generated ("sk_" + 40 hexadecimal
     * characters); only its sha256 hash and its displayable prefix are
     * persisted. The plaintext secret is returned once, to the caller,
     * and can never be recovered from the database.
     *
     * @param  list<string>  $abilities
     */
    public function handle(string $name, array $abilities, ?int $rate_limit = null, ?string $expires_at = null): CreatedApiKey
    {
        $secret = ApiKey::generateSecret();

        $apiKey = ApiKey::query()->create([
            'name' => $name,
            'prefix' => ApiKey::prefixFor($secret),
            'key_hash' => ApiKey::hashSecret($secret),
            'abilities' => $abilities,
            'rate_limit' => $rate_limit,
            'expires_at' => $expires_at,
        ]);

        return new CreatedApiKey($apiKey, $secret);
    }
}
