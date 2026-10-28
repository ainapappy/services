<?php

namespace App\Domain\Landing\Models;

use Database\Factories\Domain\Landing\Models\LandingPageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $hero_title
 * @property string $hero_subtitle
 * @property list<array{label: string, url: string, style: string}> $ctas
 * @property array{title: string, description: string} $seo
 * @property bool $is_home
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LandingPage extends Model
{
    /** @use HasFactory<LandingPageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'hero_title',
        'hero_subtitle',
        'ctas',
        'seo',
        'is_home',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ctas' => 'array',
            'seo' => 'array',
            'is_home' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include published pages.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Fill the page content from back-office form data.
     *
     * The CTA and SEO payloads are normalized so the stored JSON always
     * exposes the exact shape the front-office expects.
     *
     * @param  array{
     *     slug: string,
     *     title: string,
     *     hero_title: string,
     *     hero_subtitle: string,
     *     ctas?: list<array<string, mixed>>|null,
     *     seo?: array{title?: string|null, description?: string|null}|null,
     *     is_home?: bool|null
     * }  $data
     */
    public function fillContent(array $data): static
    {
        return $this->forceFill([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'hero_title' => $data['hero_title'],
            'hero_subtitle' => $data['hero_subtitle'],
            'ctas' => collect($data['ctas'] ?? [])
                ->map(fn (array $cta): array => [
                    'label' => (string) ($cta['label'] ?? ''),
                    'url' => (string) ($cta['url'] ?? ''),
                    'style' => (string) ($cta['style'] ?? 'primary'),
                ])
                ->values()
                ->all(),
            'seo' => [
                'title' => (string) ($data['seo']['title'] ?? ''),
                'description' => (string) ($data['seo']['description'] ?? ''),
            ],
            'is_home' => (bool) ($data['is_home'] ?? false),
        ]);
    }

    /**
     * Clear the home flag of every page except the given one.
     *
     * The table never holds two home pages: call this inside the same
     * transaction as the write that flagged a new home page.
     */
    public static function clearOtherHomeFlags(int $exceptId): void
    {
        static::query()
            ->whereKeyNot($exceptId)
            ->where('is_home', true)
            ->update(['is_home' => false]);
    }
}
