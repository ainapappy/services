<?php

namespace App\Domain\Landing\Actions;

use App\Domain\Landing\Models\LandingPage;

class DeleteLandingPage
{
    /**
     * Delete a landing page.
     *
     * Deleting the home page leaves the platform without a home: "/" then
     * answers 404 until another page is flagged as home. No automatic
     * replacement ever happens.
     */
    public function handle(LandingPage $landingPage): void
    {
        $landingPage->delete();
    }
}
