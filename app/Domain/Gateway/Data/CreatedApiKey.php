<?php

namespace App\Domain\Gateway\Data;

use App\Domain\Gateway\Models\ApiKey;

/**
 * The result of an API key creation: the persisted key plus the plaintext
 * secret. The secret exists only here — it is never stored and must be
 * handed to the caller exactly once.
 */
final class CreatedApiKey
{
    /**
     * Create the creation result.
     */
    public function __construct(
        public readonly ApiKey $apiKey,
        public readonly string $secret,
    ) {}
}
