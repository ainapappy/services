<?php

namespace App\Domain\Pricing\Http\Requests;

use App\Domain\Pricing\Models\PricingPlan;
use App\Http\Requests\Concerns\NarrowsValidatedPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class PlanRequest extends FormRequest
{
    use NarrowsValidatedPayload;

    /**
     * Authorize the request: the "can" middleware already enforced the
     * PricingPlan policy on every route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the payload before validation: kebab-case slug, trimmed
     * features. Prices are received in cents and must be whole integers.
     */
    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');

        $data = ['slug' => Str::slug(is_string($slug) ? $slug : '')];

        // A non-array features payload is left untouched so the "array"
        // rule rejects it with a clear message.
        if (is_array($this->input('features'))) {
            $data['features'] = collect($this->input('features'))
                ->map(fn (mixed $feature): string => is_scalar($feature) ? trim((string) $feature) : '')
                ->values()
                ->all();
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pricing_plans', 'slug')->ignore($this->plan()?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['required', 'string', 'max:500'],
            'price_monthly' => ['required', 'integer', 'min:0'],
            'price_yearly' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', Rule::in(['EUR'])],
            'features' => ['present', 'array', 'max:30'],
            'features.*' => ['required', 'string', 'max:255'],
            'popular' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the validated data with the precise shape documented by the
     * rules above: every field is re-checked while building the array.
     *
     * @return ($key is null ? array{
     *     slug: string,
     *     name: string,
     *     tagline: string,
     *     price_monthly: int,
     *     price_yearly: int,
     *     currency: string,
     *     features: list<string>,
     *     popular?: bool|null,
     *     active?: bool|null,
     *     sort?: int|null
     * } : mixed)
     */
    public function validated($key = null, $default = null)
    {
        if ($key !== null || $default !== null) {
            return parent::validated($key, $default);
        }

        $payload = $this->validatedPayload();

        $data = [
            'slug' => $this->stringField($payload, 'slug'),
            'name' => $this->stringField($payload, 'name'),
            'tagline' => $this->stringField($payload, 'tagline'),
            'price_monthly' => $this->intField($payload, 'price_monthly', 0) ?? 0,
            'price_yearly' => $this->intField($payload, 'price_yearly', 0) ?? 0,
            'currency' => $this->stringField($payload, 'currency'),
            'features' => collect($this->arrayField($payload, 'features'))
                ->map(fn (mixed $feature): string => is_string($feature) ? $feature : '')
                ->values()
                ->all(),
        ];

        $popular = $payload['popular'] ?? null;
        $active = $payload['active'] ?? null;
        $sort = $payload['sort'] ?? null;

        if (is_bool($popular)) {
            $data['popular'] = $popular;
        }

        if (is_bool($active)) {
            $data['active'] = $active;
        }

        if (is_int($sort)) {
            $data['sort'] = $sort;
        }

        return $data;
    }

    /**
     * The plan targeted by the request, on update routes only.
     */
    private function plan(): ?PricingPlan
    {
        $plan = $this->route('plan');

        return $plan instanceof PricingPlan ? $plan : null;
    }
}
