<?php

namespace App\Domain\Pricing\Http\Controllers\Front;

use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingPageController extends Controller
{
    /**
     * Display the public pricing page.
     *
     * Without the "table" query parameter the first published table is
     * shown (chronological publication order); with it, an unknown or
     * unpublished slug answers 404, exactly like a missing published table.
     */
    public function show(Request $request): Response
    {
        $slug = trim((string) $request->query('table', ''));

        $pricingTable = $slug === ''
            ? PricingTable::query()->published()->orderBy('published_at')->first()
            : PricingTable::query()->published()->where('slug', $slug)->first();

        if ($pricingTable === null) {
            abort(404);
        }

        $plans = $pricingTable->activePlans()
            ->get()
            ->map(fn (PricingPlan $plan): array => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'tagline' => $plan->tagline,
                'priceMonthly' => $plan->price_monthly,
                'priceYearly' => $plan->price_yearly,
                'currency' => $plan->currency,
                'features' => $plan->features,
                'popular' => $plan->popular,
            ])
            ->values()
            ->all();

        return Inertia::render('front/Pricing/Show', [
            'table' => [
                'slug' => $pricingTable->slug,
                'title' => $pricingTable->title,
                'subtitle' => $pricingTable->subtitle,
                'plans' => $plans,
                'faq' => $pricingTable->faq,
            ],
        ]);
    }
}
