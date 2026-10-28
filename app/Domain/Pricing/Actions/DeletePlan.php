<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingPlan;

class DeletePlan
{
    /**
     * Delete a pricing plan.
     *
     * The pivot rows referencing the plan are removed by the database
     * cascade; every pricing table it belonged to simply stops listing it.
     */
    public function handle(PricingPlan $plan): void
    {
        $plan->delete();
    }
}
