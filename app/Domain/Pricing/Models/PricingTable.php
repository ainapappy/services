<?php

namespace App\Domain\Pricing\Models;

use Database\Factories\Domain\Pricing\Models\PricingTableFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $subtitle
 * @property list<array{question: string, answer: string}> $faq
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PricingTable extends Model
{
    /** @use HasFactory<PricingTableFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'faq',
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
            'faq' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * The plans attached to the table, ordered by the pivot sort column.
     *
     * @return BelongsToMany<PricingPlan, $this>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(PricingPlan::class, 'pricing_table_plan')
            ->withPivot('sort')
            ->orderByPivot('sort');
    }

    /**
     * The active plans attached to the table, ordered by the pivot sort
     * column: inactive plans never render on the public pricing page.
     *
     * @return BelongsToMany<PricingPlan, $this>
     */
    public function activePlans(): BelongsToMany
    {
        return $this->plans()->where('pricing_plans.active', true);
    }

    /**
     * Scope a query to only include published tables.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Fill the table content from back-office form data.
     *
     * The FAQ payload is normalized so the stored JSON always exposes the
     * exact shape the front-office expects. The publication state is left
     * untouched (dedicated endpoint).
     *
     * @param  array{
     *     slug: string,
     *     title: string,
     *     subtitle: string,
     *     faq?: list<array{question: mixed, answer: mixed}>|null
     * }  $data
     */
    public function fillContent(array $data): static
    {
        return $this->forceFill([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'faq' => collect($data['faq'] ?? [])
                ->map(fn (array $entry): array => [
                    'question' => (string) ($entry['question'] ?? ''),
                    'answer' => (string) ($entry['answer'] ?? ''),
                ])
                ->values()
                ->all(),
        ]);
    }
}
