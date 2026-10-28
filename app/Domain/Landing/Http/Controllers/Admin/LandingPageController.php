<?php

namespace App\Domain\Landing\Http\Controllers\Admin;

use App\Domain\Landing\Actions\CreateLandingPage;
use App\Domain\Landing\Actions\DeleteLandingPage;
use App\Domain\Landing\Actions\PublishLandingPage;
use App\Domain\Landing\Actions\UpdateLandingPage;
use App\Domain\Landing\Http\Requests\CreateLandingPageRequest;
use App\Domain\Landing\Http\Requests\UpdateLandingPageRequest;
use App\Domain\Landing\Models\LandingPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    /**
     * Display the landing pages list.
     */
    public function index(): Response
    {
        $landingPages = LandingPage::query()
            ->orderBy('title')
            ->get()
            ->map(fn (LandingPage $page): array => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'isHome' => $page->is_home,
                'publishedAt' => $page->published_at?->toISOString(),
                'ctasCount' => count($page->ctas),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Landing/Index', ['landingPages' => $landingPages]);
    }

    /**
     * Display the landing page creation form.
     */
    public function create(): Response
    {
        return Inertia::render('back/Landing/Form');
    }

    /**
     * Store a new landing page.
     */
    public function store(CreateLandingPageRequest $request, CreateLandingPage $action): RedirectResponse
    {
        $landingPage = $action->handle($request->validated());

        // Pages start as drafts: the confirmation toast names the new
        // entry so it is easy to spot in the list, and reminds that it
        // stays invisible on the front-office until published.
        return redirect()
            ->route('admin.landing.index')
            ->with('toast', [
                'type' => 'success',
                'message' => sprintf(
                    'Page « %s » créée en brouillon : publiez-la pour la rendre visible côté vitrine.',
                    $landingPage->title,
                ),
            ]);
    }

    /**
     * Display the landing page edition form.
     */
    public function edit(LandingPage $landingPage): Response
    {
        return Inertia::render('back/Landing/Form', [
            'landingPage' => [
                'id' => $landingPage->id,
                'slug' => $landingPage->slug,
                'title' => $landingPage->title,
                'heroTitle' => $landingPage->hero_title,
                'heroSubtitle' => $landingPage->hero_subtitle,
                'ctas' => $landingPage->ctas,
                'seo' => $landingPage->seo,
                'isHome' => $landingPage->is_home,
                'publishedAt' => $landingPage->published_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Update a landing page.
     */
    public function update(UpdateLandingPageRequest $request, LandingPage $landingPage, UpdateLandingPage $action): RedirectResponse
    {
        $action->handle($landingPage, $request->validated());

        return redirect()->route('admin.landing.index');
    }

    /**
     * Publish or unpublish a landing page.
     */
    public function publish(LandingPage $landingPage, PublishLandingPage $action): RedirectResponse
    {
        $action->handle($landingPage);

        return redirect()->route('admin.landing.index');
    }

    /**
     * Delete a landing page.
     */
    public function destroy(LandingPage $landingPage, DeleteLandingPage $action): RedirectResponse
    {
        $action->handle($landingPage);

        return redirect()->route('admin.landing.index');
    }
}
