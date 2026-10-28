<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingTable;

class UpdateTable
{
    /**
     * Update a pricing table with validated back-office data.
     *
     * The publication state is left untouched (dedicated endpoint). The
     * submitted plans replace the previous selection, keeping the given
     * order.
     *
     * @param  array{
     *     slug: string,
     *     title: string,
     *     subtitle: string,
     *     faq: list<array{question: string, answer: string}>,
     *     plan_ids: list<int>
     * }  $data
     */
    public function handle(PricingTable $pricingTable, array $data): PricingTable
    {
        $pricingTable->fillContent($data)->save();

        (new SyncTablePlans)->handle($pricingTable, $data['plan_ids']);

        return $pricingTable;
    }
}
