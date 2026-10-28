<?php

namespace App\Domain\Landing\Actions;

use App\Domain\Landing\Models\LandingPage;
use Illuminate\Support\Facades\DB;

class CreateLandingPage
{
    /**
     * Create a landing page from validated back-office data.
     *
     * Pages are created unpublished; the publish endpoint flips them. When
     * the new page is flagged as home, every other page loses its own flag
     * in the same transaction: the table never holds two home pages.
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
    public function handle(array $data): LandingPage
    {
        return DB::transaction(function () use ($data): LandingPage {
            $landingPage = (new LandingPage)->fillContent($data);
            $landingPage->save();

            if ($landingPage->is_home) {
                LandingPage::clearOtherHomeFlags($landingPage->id);
            }

            return $landingPage;
        });
    }
}
