<?php

namespace App\Domain\Webhooks\Http\Requests;

use App\Domain\Webhooks\WebhookEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules of the endpoint forms: every field is required on
 * creation and optional on update, where an omitted field means "keep
 * the stored value". The secret never travels through these forms: it
 * only changes through regeneration.
 */
abstract class WebhookEndpointRequest extends FormRequest
{
    /**
     * Authorize the request: the "can:manage-settings" middleware already
     * enforced the administrator-only gate on every webhook route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $presence = $this->creates() ? 'required' : 'sometimes';

        return [
            'url' => [$presence, 'url:http,https', 'max:2048'],
            'events' => [$presence, 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in(WebhookEvent::values())],
            'active' => [$presence, 'boolean'],
        ];
    }

    /**
     * Whether the request creates an endpoint (store route).
     */
    abstract protected function creates(): bool;
}
