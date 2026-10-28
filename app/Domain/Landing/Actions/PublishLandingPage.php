<?php

namespace App\Domain\Landing\Actions;

use App\Domain\Landing\Events\LandingPublished;
use App\Domain\Landing\Models\LandingPage;

class PublishLandingPage
{
    /**
     * Toggle the publication of a landing page: publish it when it is not
     * published yet, unpublish it otherwise. Publishing fires the
     * LandingPublished event once; unpublishing stays silent.
     */
    public function handle(LandingPage $landingPage): LandingPage
    {
        $wasPublished = $landingPage->published_at !== null;

        $landingPage->forceFill([
            'published_at' => $wasPublished ? null : now(),
        ])->save();

        if (! $wasPublished) {
            LandingPublished::dispatch($landingPage);
        }

        return $landingPage;
    }
}
