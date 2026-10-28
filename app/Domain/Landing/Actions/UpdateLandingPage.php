<?php

namespace App\Domain\Landing\Actions;

use App\Domain\Landing\Models\LandingPage;
use Illuminate\Support\Facades\DB;

class UpdateLandingPage
{
    /**
     * Update a landing page with validated back-office data.
     *
     * The publication state is left untouched (dedicated endpoint). When
     * the page is flagged as home, every other page loses its own flag in
     * the same transaction.
     *
     * @param  array{
     *     slug: string,
     *     title: string,
     *     hero_title: string,
     *     hero_subtitle: string,
     *     ctas: list<array{label: string, url: string, style: string}>,
     *     seo: array{title?: string|null, description?: string|null},
     *     is_home?: bool|null
     * }  $data
     */
    public function handle(LandingPage $landingPage, array $data): LandingPage
    {
        return DB::transaction(function () use ($landingPage, $data): LandingPage {
            $landingPage->fillContent($data)->save();

            if ($landingPage->is_home) {
                LandingPage::clearOtherHomeFlags($landingPage->id);
            }

            return $landingPage;
        });
    }
}
