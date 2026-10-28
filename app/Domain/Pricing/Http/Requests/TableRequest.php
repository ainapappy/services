<?php

namespace App\Domain\Pricing\Http\Requests;

use App\Domain\Pricing\Models\PricingTable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class TableRequest extends FormRequest
{
    /**
     * Authorize the request: the "can" middleware already enforced the
     * PricingTable policy on every route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the payload before validation: kebab-case slug, trimmed
     * FAQ entries, integer plan identifiers.
     */
    protected function prepareForValidation(): void
    {
        $data = ['slug' => Str::slug((string) $this->input('slug'))];

        // Non-array faq / plan_ids payloads are left untouched so the
        // "array" rule rejects them with a clear message.
        if (is_array($this->input('faq'))) {
            $data['faq'] = collect($this->input('faq'))
                ->map(fn (mixed $entry): array => is_array($entry) ? [
                    'question' => trim((string) ($entry['question'] ?? '')),
                    'answer' => trim((string) ($entry['answer'] ?? '')),
                ] : ['question' => '', 'answer' => ''])
                ->values()
                ->all();
        }

        if (is_array($this->input('plan_ids'))) {
            $data['plan_ids'] = collect($this->input('plan_ids'))
                ->map(fn (mixed $id): int => (int) $id)
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
                Rule::unique('pricing_tables', 'slug')->ignore($this->pricingTable()?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['required', 'string', 'max:500'],
            'faq' => ['present', 'array', 'max:30'],
            'faq.*' => ['required', 'array'],
            'faq.*.question' => ['required', 'string', 'max:255'],
            'faq.*.answer' => ['required', 'string', 'max:2000'],
            'plan_ids' => ['present', 'array'],
            'plan_ids.*' => ['integer', Rule::exists('pricing_plans', 'id')],
        ];
    }

    /**
     * Get the validated data with a precise shape, as documented by the
     * rules above.
     *
     * @return array{
     *     slug: string,
     *     title: string,
     *     subtitle: string,
     *     faq: list<array{question: string, answer: string}>,
     *     plan_ids: list<int>
     * }
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }

    /**
     * The pricing table targeted by the request, on update routes only.
     */
    private function pricingTable(): ?PricingTable
    {
        $pricingTable = $this->route('pricingTable');

        return $pricingTable instanceof PricingTable ? $pricingTable : null;
    }
}
