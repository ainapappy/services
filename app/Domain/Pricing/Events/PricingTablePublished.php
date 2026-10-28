<?php

namespace App\Domain\Pricing\Events;

use App\Domain\Pricing\Models\PricingTable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once, when a pricing table transitions from unpublished to
 * published. Unpublishing never fires it. The Webhooks module listens
 * to it to push the published table to subscribed endpoints.
 */
final class PricingTablePublished
{
    use Dispatchable;

    /**
     * Create the event.
     */
    public function __construct(
        public readonly PricingTable $pricingTable,
    ) {}
}
