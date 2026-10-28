<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Events\PricingTablePublished;
use App\Domain\Pricing\Models\PricingTable;

class PublishTable
{
    /**
     * Toggle the publication of a pricing table: publish it when it is not
     * published yet, unpublish it otherwise. Unpublished tables never
     * render on the public pricing page. Publishing fires the
     * PricingTablePublished event once; unpublishing stays silent.
     */
    public function handle(PricingTable $pricingTable): PricingTable
    {
        $wasPublished = $pricingTable->published_at !== null;

        $pricingTable->forceFill([
            'published_at' => $wasPublished ? null : now(),
        ])->save();

        if (! $wasPublished) {
            PricingTablePublished::dispatch($pricingTable);
        }

        return $pricingTable;
    }
}
