<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingTable;

class CreateTable
{
    /**
     * Create a pricing table from validated back-office data.
     *
     * Tables are created unpublished; the publish endpoint flips them. The
     * submitted plans are attached in the given order in the same
     * transaction.
     *
     * @param  array{
     *     slug: string,
     *     title: string,
     *     subtitle: string,
     *     faq: list<array{question: string, answer: string}>,
     *     plan_ids: list<int>
     * }  $data
     */
    public function handle(array $data): PricingTable
    {
        $pricingTable = new PricingTable;

        $pricingTable->fillContent($data)->save();

        (new SyncTablePlans)->handle($pricingTable, $data['plan_ids']);

        return $pricingTable;
    }
}
