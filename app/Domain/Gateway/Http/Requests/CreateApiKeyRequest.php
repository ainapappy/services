<?php

namespace App\Domain\Gateway\Http\Requests;

use App\Http\Requests\Concerns\NarrowsValidatedPayload;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
    use NarrowsValidatedPayload;

    /**
     * Authorize the request: the "can:manage-settings" middleware already
     * enforced the administrator-only gate on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1', 'max:20'],
            'abilities.*' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9-]*:[a-z][a-z0-9-]*$/',
            ],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Get the validated data with the precise shape documented by the
     * rules above: every field is re-checked while building the array.
     *
     * @return ($key is null ? array{
     *     name: string,
     *     abilities: list<string>,
     *     rate_limit?: int|null,
     *     expires_at?: string|null
     * } : mixed)
     */
    public function validated($key = null, $default = null)
    {
        if ($key !== null || $default !== null) {
            return parent::validated($key, $default);
        }

        $payload = $this->validatedPayload();

        $data = [
            'name' => $this->stringField($payload, 'name'),
            'abilities' => collect($this->arrayField($payload, 'abilities'))
                ->map(fn (mixed $ability): string => is_string($ability) ? $ability : '')
                ->values()
                ->all(),
        ];

        $rateLimit = $payload['rate_limit'] ?? null;
        $expiresAt = $payload['expires_at'] ?? null;

        if (is_int($rateLimit)) {
            $data['rate_limit'] = $rateLimit;
        }

        if (is_string($expiresAt)) {
            $data['expires_at'] = $expiresAt;
        }

        return $data;
    }
}
