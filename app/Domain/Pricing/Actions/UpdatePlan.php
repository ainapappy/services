<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingPlan;

class UpdatePlan
{
    /**
     * Update a pricing plan with validated back-office data.
     *
     * The active flag is left untouched (dedicated toggle endpoint): the
     * current value is re-injected so an absent form field can never
     * silently deactivate a plan.
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
    public function handle(PricingPlan $plan, array $data): PricingPlan
    {
        $data['active'] = $plan->active;

        $plan->fillContent($data)->save();

        return $plan;
    }
}
