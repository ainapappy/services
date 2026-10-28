<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingPlan;

class TogglePlan
{
    /**
     * Toggle the activation of a pricing plan: activate it when it is not
     * active yet, deactivate it otherwise. Inactive plans never show up in
     * the public pricing table.
     */
    public function handle(PricingPlan $plan): PricingPlan
    {
        $plan->forceFill(['active' => ! $plan->active])->save();

        return $plan;
    }
}
