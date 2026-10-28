<?php

namespace App\Domain\Monitoring\Http\Requests;

use App\Domain\Monitoring\Models\Monitor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules of the monitor forms: every field is required on
 * creation and optional on update, where an omitted field means "keep
 * the stored value". The target only carries a connector id for the
 * "connector" type; the gateway and webhook monitors watch fixed
 * platform targets and carry no target of their own.
 */
abstract class MonitorRequest extends FormRequest
{
    /**
     * Authorize the request: the "can:manage-settings" middleware already
     * enforced the administrator-only gate on every monitoring route.
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
            'name' => [$presence, 'string', 'max:255'],
            'type' => [$presence, 'string', Rule::in(Monitor::TYPES)],
            'target' => ['sometimes', 'array'],
            'target.connectorId' => [
                $this->connectorIdRequired() ? 'required' : 'sometimes',
                'integer',
                'exists:connectors,id',
            ],
            'latency_threshold_ms' => ['nullable', 'integer', 'min:0'],
            'active' => [$presence, 'boolean'],
        ];
    }

    /**
     * Whether the request creates a monitor (store route).
     */
    abstract protected function creates(): bool;

    /**
     * Whether the payload must name the targeted connector: always on
     * creation of a connector monitor, and on update whenever the type
     * switches to "connector" without naming one — otherwise the monitor
     * would end up with a dangling target.
     */
    private function connectorIdRequired(): bool
    {
        if ($this->creates()) {
            return $this->targetType() === Monitor::TYPE_CONNECTOR;
        }

        return $this->has('type')
            && $this->targetType() === Monitor::TYPE_CONNECTOR
            && ! $this->has('target.connectorId');
    }

    /**
     * The type the target is validated against: the payload type,
     * falling back to the routed monitor type on update when the payload
     * omits it.
     */
    private function targetType(): ?string
    {
        $type = $this->input('type');

        if (is_string($type) && in_array($type, Monitor::TYPES, true)) {
            return $type;
        }

        $monitor = $this->route('monitor');

        return $monitor instanceof Monitor ? $monitor->type : null;
    }
}
