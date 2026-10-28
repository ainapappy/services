<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingTable;

class PublishTable
{
    /**
     * Toggle the publication of a pricing table: publish it when it is not
     * published yet, unpublish it otherwise. Unpublished tables never
     * render on the public pricing page.
     */
    public function handle(PricingTable $pricingTable): PricingTable
    {
        $pricingTable->forceFill([
            'published_at' => $pricingTable->published_at === null ? now() : null,
        ])->save();

        return $pricingTable;
    }
}
