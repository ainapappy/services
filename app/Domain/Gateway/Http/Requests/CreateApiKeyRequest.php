<?php

namespace App\Domain\Gateway\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
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
     * Get the validated data with a precise shape, as documented by the
     * rules above.
     *
     * @return array{
     *     name: string,
     *     abilities: list<string>,
     *     rate_limit?: int|null,
     *     expires_at?: string|null
     * }
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}
