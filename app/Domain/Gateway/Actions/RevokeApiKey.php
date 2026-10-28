<?php

namespace App\Domain\Gateway\Actions;

use App\Domain\Gateway\Models\ApiKey;

class RevokeApiKey
{
    /**
     * Revoke an API key: a revoked key is rejected at once, without
     * redeployment, but stays in the database for history. Revocation is
     * idempotent: revoking an already revoked key keeps its original date.
     */
    public function handle(ApiKey $apiKey): ApiKey
    {
        if ($apiKey->revoked_at === null) {
            $apiKey->forceFill(['revoked_at' => now()])->save();
        }

        return $apiKey;
    }
}
