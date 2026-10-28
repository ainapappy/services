<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingPlan;

class CreatePlan
{
    /**
     * Create a pricing plan from validated back-office data.
     *
     * The "active" flag comes from the request; the toggle endpoint can flip it later.
     *
     * @param  array{
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
     * }  $data
     */
    public function handle(array $data): PricingPlan
    {
        $plan = new PricingPlan;

        $plan->fillContent($data)->save();

        return $plan;
    }
}
