<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingTable;

class DeleteTable
{
    /**
     * Delete a pricing table.
     *
     * The pivot rows are removed by the database cascade; the plans
     * themselves stay available for the other tables.
     */
    public function handle(PricingTable $pricingTable): void
    {
        $pricingTable->delete();
    }
}
