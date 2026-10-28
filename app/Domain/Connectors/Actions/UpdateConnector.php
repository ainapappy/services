<?php

namespace App\Domain\Connectors\Actions;

use App\Domain\Connectors\Models\Connector;

class UpdateConnector
{
    /**
     * Update a connector with validated back-office data.
     *
     * Omitted fields are left untouched. The credentials get special
     * care: they are never sent back to the client, so the edition form
     * submits empty fields for "keep the stored value". A provided field
     * replaces its counterpart and the merged payload is re-encrypted by
     * the model cast; when nothing new is provided, the stored ciphertext
     * stays byte-identical. The custom type is the exception: its single
     * JSON textarea always submits a complete object, so a provided
     * payload replaces the stored one instead of merging with it.
     *
     * @param  array{
     *     name?: string,
     *     type?: string,
     *     base_url?: string,
     *     credentials?: array<string, mixed>
     * }  $data
     */
    public function handle(Connector $connector, array $data): Connector
    {
        $connector->fill([
            'name' => $data['name'] ?? $connector->name,
            'type' => $data['type'] ?? $connector->type,
            'base_url' => $data['base_url'] ?? $connector->base_url,
        ]);

        $provided = collect($data['credentials'] ?? [])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();

        if ($provided !== []) {
            // Assignment goes through the encrypted cast: the stored
            // ciphertext is replaced by the new payload.
            $connector->credentials = $connector->type === Connector::TYPE_CUSTOM
                ? $provided
                : array_merge($connector->credentials, $provided);
        }

        $connector->save();

        return $connector;
    }
}
