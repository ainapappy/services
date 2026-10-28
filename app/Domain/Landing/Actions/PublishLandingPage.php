<?php

namespace App\Domain\Landing\Actions;

use App\Domain\Landing\Models\LandingPage;

class PublishLandingPage
{
    /**
     * Toggle the publication of a landing page: publish it when it is not
     * published yet, unpublish it otherwise.
     */
    public function handle(LandingPage $landingPage): LandingPage
    {
        $landingPage->forceFill([
            'published_at' => $landingPage->published_at === null ? now() : null,
        ])->save();

        return $landingPage;
    }
}
