<?php

namespace App\Domain\Connectors\Actions;

use App\Domain\Connectors\Models\Connector;

class CreateConnector
{
    /**
     * Create a connector from validated back-office data.
     *
     * The credentials array is encrypted by the model cast at write time:
     * the plaintext exists here in memory only and never reaches the
     * database, the logs or a serialized response. A new connector starts
     * untested, with the "unknown" status.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function handle(string $name, string $type, string $base_url, array $credentials): Connector
    {
        return Connector::query()->create([
            'name' => $name,
            'type' => $type,
            'base_url' => $base_url,
            'credentials' => $credentials,
            'status' => Connector::STATUS_UNKNOWN,
            'last_error' => null,
        ]);
    }
}
