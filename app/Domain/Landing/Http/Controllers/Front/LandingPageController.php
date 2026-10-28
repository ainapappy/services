<?php

namespace App\Domain\Landing\Http\Controllers\Front;

use App\Domain\Landing\Models\LandingPage;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    /**
     * Display the published home landing page.
     */
    public function home(): Response
    {
        $landingPage = LandingPage::query()
            ->published()
            ->where('is_home', true)
            ->first();

        return $this->renderShow($landingPage);
    }

    /**
     * Display a published landing page by slug.
     */
    public function show(LandingPage $landingPage): Response
    {
        return $this->renderShow($landingPage->published_at === null ? null : $landingPage);
    }

    /**
     * Render the public landing page props, 404 when unavailable.
     */
    private function renderShow(?LandingPage $landingPage): Response
    {
        if ($landingPage === null) {
            abort(404);
        }

        return Inertia::render('front/Landing/Show', [
            'landing' => [
                'slug' => $landingPage->slug,
                'title' => $landingPage->title,
                'heroTitle' => $landingPage->hero_title,
                'heroSubtitle' => $landingPage->hero_subtitle,
                'ctas' => $landingPage->ctas,
                'seo' => $landingPage->seo,
            ],
        ]);
    }
}
