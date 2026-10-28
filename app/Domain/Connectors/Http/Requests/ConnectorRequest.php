<?php

namespace App\Domain\Connectors\Http\Requests;

use App\Domain\Connectors\Models\Connector;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules of the connector forms: every field is required on
 * creation and optional on update, where an omitted field means "keep
 * the stored value". The credentials rules depend on the target type:
 * username + password for WordPress, api_key + secret for Caren and
 * Erpen, a free-form array for custom.
 */
abstract class ConnectorRequest extends FormRequest
{
    /**
     * Authorize the request: the "can" middleware already enforced the
     * Connector policy on every route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the payload before validation: on update, the edition
     * form submits empty credential fields for "keep the stored value",
     * so those fields are dropped instead of failing the "string" rule.
     */
    protected function prepareForValidation(): void
    {
        if ($this->creates()) {
            return;
        }

        $credentials = $this->input('credentials');

        if (is_array($credentials)) {
            $this->merge([
                'credentials' => collect($credentials)
                    ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
                    ->all(),
            ]);
        }
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
            'name' => [$presence, 'string', 'max:255'],
            'type' => [$presence, 'string', Rule::in(Connector::TYPES)],
            'base_url' => [$presence, 'url:http,https', 'max:255'],
            'credentials' => [$presence, 'array'],
            ...$this->credentialRules(),
        ];
    }

    /**
     * Whether the request creates a connector (store route).
     */
    abstract protected function creates(): bool;

    /**
     * The per-type credential rules: on creation every field of the type
     * is required; on update provided fields replace their counterpart
     * and empty or omitted fields keep the stored value.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    private function credentialRules(): array
    {
        $presence = $this->creates() ? 'required' : 'sometimes';

        return match ($this->targetType()) {
            Connector::TYPE_WORDPRESS => [
                'credentials.username' => [$presence, 'string', 'max:255'],
                'credentials.password' => [$presence, 'string', 'max:1024'],
            ],
            Connector::TYPE_CAREN, Connector::TYPE_ERPEN => [
                'credentials.api_key' => [$presence, 'string', 'max:255'],
                'credentials.secret' => [$presence, 'string', 'max:1024'],
            ],
            default => [],
        };
    }

    /**
     * The type the credentials are validated against: the payload type,
     * falling back to the routed connector type on update when the
     * payload omits it.
     */
    private function targetType(): string
    {
        $type = $this->input('type');

        if (is_string($type) && in_array($type, Connector::TYPES, true)) {
            return $type;
        }

        $connector = $this->route('connector');

        return $connector instanceof Connector ? $connector->type : '';
    }
}
