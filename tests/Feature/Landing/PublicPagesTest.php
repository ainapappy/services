<?php

use App\Domain\Landing\Actions\PublishLandingPage;
use App\Domain\Landing\Models\LandingPage;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'landing')->update(['enabled' => true]);
    $this->withoutVite();
});

test('the root answers 404 when no home page is published', function () {
    LandingPage::factory()->create();

    $this->get('/')->assertNotFound();
});

test('the root answers 404 when the home page is not published', function () {
    LandingPage::factory()->home()->create();

    $this->get('/')->assertNotFound();
});

test('the root renders the published home page', function () {
    LandingPage::factory()->home()->published()->create(['slug' => 'accueil']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Landing/Show', false)
            ->where('landing.slug', 'accueil'));
});

test('an unknown slug answers 404', function () {
    $this->get('/n-importe-quoi')->assertNotFound();
});

test('a landing page answers 404 before publication and 200 after', function () {
    $page = LandingPage::factory()->create(['slug' => 'ma-page']);

    $this->get('/ma-page')->assertNotFound();

    (new PublishLandingPage)->handle($page);

    $this->get('/ma-page')->assertOk();
});

test('an unpublished page answers 404 even when its slug exists', function () {
    LandingPage::factory()->create(['slug' => 'brouillon']);

    $this->get('/brouillon')->assertNotFound();
});

test('an anonymous visitor sees a published landing page', function () {
    LandingPage::factory()->published()->create(['slug' => 'publique']);

    $this->get('/publique')->assertOk();
});

test('the public view receives the persisted CTAs', function () {
    LandingPage::factory()->published()->create([
        'slug' => 'cta-test',
        'title' => 'Titre CTA',
        'ctas' => [
            ['label' => 'Demarrer', 'url' => 'https://services.test/register', 'style' => 'primary'],
            ['label' => 'Tarifs', 'url' => '/pricing', 'style' => 'secondary'],
        ],
    ]);

    $this->get('/cta-test')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Landing/Show', false)
            ->where('landing.title', 'Titre CTA')
            ->has('landing.ctas', 2)
            ->where('landing.ctas.0.label', 'Demarrer')
            ->where('landing.ctas.0.url', 'https://services.test/register')
            ->where('landing.ctas.0.style', 'primary')
            ->where('landing.ctas.1.label', 'Tarifs')
            ->where('landing.ctas.1.url', '/pricing')
            ->where('landing.ctas.1.style', 'secondary')
            ->has('landing.seo.title')
            ->has('landing.seo.description'));
});

test('disabling the module makes every public landing route 404', function () {
    LandingPage::factory()->home()->published()->create(['slug' => 'accueil']);
    LandingPage::factory()->published()->create(['slug' => 'autre']);

    Module::query()->where('slug', 'landing')->update(['enabled' => false]);

    $this->get('/')->assertNotFound();
    $this->get('/accueil')->assertNotFound();
    $this->get('/autre')->assertNotFound();
});
