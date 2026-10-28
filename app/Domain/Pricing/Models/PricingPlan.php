<?php

namespace App\Domain\Pricing\Models;

use Database\Factories\Domain\Pricing\Models\PricingPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $tagline
 * @property int $price_monthly
 * @property int $price_yearly
 * @property string $currency
 * @property list<string> $features
 * @property bool $popular
 * @property bool $active
 * @property int $sort
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PricingPlan extends Model
{
    /** @use HasFactory<PricingPlanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'price_monthly',
        'price_yearly',
        'currency',
        'features',
        'popular',
        'active',
        'sort',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'features' => 'array',
            'popular' => 'boolean',
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * Fill the plan content from back-office form data.
     *
     * Prices are stored in cents; the euro/cents conversion is a back-office
     * UI concern and never happens in the domain. The features payload is
     * normalized so the stored JSON always exposes a list of plain strings.
     *
     * @param  array{
     *     slug: string,
     *     name: string,
     *     tagline: string,
     *     price_monthly: int,
     *     price_yearly: int,
     *     currency: string,
     *     features?: list<mixed>|null,
     *     popular?: bool|null,
     *     active?: bool|null,
     *     sort?: int|null
     * }  $data
     */
    public function fillContent(array $data): static
    {
        return $this->forceFill([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'tagline' => $data['tagline'],
            'price_monthly' => (int) $data['price_monthly'],
            'price_yearly' => (int) $data['price_yearly'],
            'currency' => $data['currency'],
            'features' => collect(is_array($data['features'] ?? null) ? $data['features'] : [])
                ->map(fn (mixed $feature): string => is_string($feature) ? $feature : '')
                ->values()
                ->all(),
            'popular' => (bool) ($data['popular'] ?? false),
            'active' => (bool) ($data['active'] ?? false),
            'sort' => (int) ($data['sort'] ?? 0),
        ]);
    }
}
