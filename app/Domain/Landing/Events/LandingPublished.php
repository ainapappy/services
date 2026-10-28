<?php

namespace App\Domain\Landing\Events;

use App\Domain\Landing\Models\LandingPage;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once, when a landing page transitions from unpublished to
 * published. Unpublishing never fires it. The Webhooks module listens
 * to it to push the published content to subscribed endpoints.
 */
final class LandingPublished
{
    use Dispatchable;

    /**
     * Create the event.
     */
    public function __construct(
        public readonly LandingPage $landingPage,
    ) {}
}
