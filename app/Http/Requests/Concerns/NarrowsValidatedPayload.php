<?php

namespace App\Http\Requests\Concerns;

/**
 * Narrows the validated payload of a form request to the precise shape
 * documented by its rules.
 *
 * FormRequest::validated() is typed mixed by the framework because the
 * signature also serves single-key reads; the payload itself is only
 * guaranteed field by field by the rules. These helpers re-check every
 * field while building the array, so the documented shape rests on
 * runtime guarantees instead of trust.
 */
trait NarrowsValidatedPayload
{
    /**
     * The full validated payload as a plain array.
     *
     * @return array<array-key, mixed>
     */
    protected function validatedPayload(): array
    {
        return parent::validated();
    }

    /**
     * The string value at the key, or the fallback.
     *
     * @param  array<array-key, mixed>  $payload
     */
    protected function stringField(array $payload, string $key, string $fallback = ''): string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? $value : $fallback;
    }

    /**
     * The string value at the key, or null.
     *
     * @param  array<array-key, mixed>  $payload
     */
    protected function nullStringField(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * The integer value at the key, or the fallback.
     *
     * @param  array<array-key, mixed>  $payload
     */
    protected function intField(array $payload, string $key, ?int $fallback = null): ?int
    {
        $value = $payload[$key] ?? null;

        return is_int($value) ? $value : $fallback;
    }

    /**
     * The boolean value at the key, or the fallback.
     *
     * @param  array<array-key, mixed>  $payload
     */
    protected function boolField(array $payload, string $key, ?bool $fallback = null): ?bool
    {
        $value = $payload[$key] ?? null;

        return is_bool($value) ? $value : $fallback;
    }

    /**
     * The array value at the key, or an empty array.
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    protected function arrayField(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        return is_array($value) ? $value : [];
    }
}
