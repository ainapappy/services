<?php

namespace App\Domain\Gateway\Http\Controllers\Api;

use App\Domain\Landing\Models\LandingPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    /**
     * List the published landing pages. The existing Landing model is the
     * single source of truth; drafts never appear in the response.
     */
    public function index(): JsonResponse
    {
        $landings = LandingPage::query()
            ->published()
            ->orderBy('slug')
            ->get()
            ->map(fn (LandingPage $page): array => [
                'slug' => $page->slug,
                'title' => $page->title,
                'heroTitle' => $page->hero_title,
                'heroSubtitle' => $page->hero_subtitle,
                'ctas' => $page->ctas,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $landings]);
    }
}
