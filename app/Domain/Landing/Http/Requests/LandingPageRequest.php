<?php

namespace App\Domain\Landing\Http\Requests;

use App\Domain\Landing\Models\LandingPage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class LandingPageRequest extends FormRequest
{
    /**
     * Authorize the request: the "can" middleware already enforced the
     * LandingPage policy on every route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the slug to its kebab-case form before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('slug')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('landing_pages', 'slug')->ignore($this->landingPage()?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['required', 'string', 'max:500'],
            'ctas' => ['present', 'array', 'max:8'],
            'ctas.*.label' => ['required', 'string', 'max:255'],
            'ctas.*.url' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && $this->isValidCtaUrl($value)) {
                        return;
                    }

                    $fail('The CTA url must be an absolute http(s) url or a path starting with "/".');
                },
            ],
            'ctas.*.style' => ['required', 'string', 'in:primary,secondary'],
            'seo' => ['present', 'array'],
            'seo.title' => ['nullable', 'string', 'max:255'],
            'seo.description' => ['nullable', 'string', 'max:500'],
            'is_home' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the validated data with a precise shape, as documented by the
     * rules above.
     *
     * @return array{
     *     slug: string,
     *     title: string,
     *     hero_title: string,
     *     hero_subtitle: string,
     *     ctas: list<array{label: string, url: string, style: string}>,
     *     seo: array{title?: string|null, description?: string|null},
     *     is_home?: bool|null
     * }
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }

    /**
     * The landing page targeted by the request, on update routes only.
     */
    private function landingPage(): ?LandingPage
    {
        $landingPage = $this->route('landingPage');

        return $landingPage instanceof LandingPage ? $landingPage : null;
    }

    /**
     * A CTA url is either an absolute http(s) url or a site-relative path.
     */
    private function isValidCtaUrl(string $url): bool
    {
        $absolute = preg_match('#^https?://\S+$#i', $url) === 1;
        $relative = preg_match('#^/\S*$#', $url) === 1;

        return $absolute || $relative;
    }
}
