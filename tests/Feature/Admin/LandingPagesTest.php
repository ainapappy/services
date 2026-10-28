<?php

use App\Domain\Landing\Models\LandingPage;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'landing')->update(['enabled' => true]);
    $this->withoutVite();
});

function landingPayload(array $overrides = []): array
{
    return array_merge([
        'slug' => 'nouvelle-page',
        'title' => 'Nouvelle page',
        'hero_title' => 'Bienvenue sur Services',
        'hero_subtitle' => 'Tous vos services au même endroit.',
        'ctas' => [
            ['label' => 'Commencer', 'url' => 'https://services.test/register', 'style' => 'primary'],
            ['label' => 'En savoir plus', 'url' => 'https://services.test/pricing', 'style' => 'secondary'],
        ],
        'seo' => ['title' => 'Nouvelle page - Services', 'description' => 'Tous vos services au même endroit.'],
        'is_home' => false,
    ], $overrides);
}

function userWithRole(string $role): User
{
    return $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.landing.index'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $admin = User::factory()->unverified()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.landing.index'))
        ->assertRedirect(route('verification.notice'));
});

test('editors and administrators can visit the landing index', function (string $role) {
    $user = userWithRole($role);

    $this->actingAs($user)
        ->get(route('admin.landing.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Landing/Index', false)
            ->where('modules.0.slug', 'landing')
            ->where('modules.0.enabled', true));
})->with(['admin', 'editor']);

test('administrators see every landing page in the index', function () {
    $admin = User::factory()->admin()->create();

    $landingPage = LandingPage::factory()->published()->create([
        'slug' => 'page-visible',
        'title' => 'Page visible',
        'ctas' => [
            ['label' => 'A', 'url' => 'https://a.test/x', 'style' => 'primary'],
            ['label' => 'B', 'url' => 'https://b.test/y', 'style' => 'secondary'],
            ['label' => 'C', 'url' => 'https://c.test/z', 'style' => 'primary'],
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.landing.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Landing/Index', false)
            ->has('landingPages', 1)
            ->where('landingPages.0.id', $landingPage->id)
            ->where('landingPages.0.slug', 'page-visible')
            ->where('landingPages.0.title', 'Page visible')
            ->where('landingPages.0.isHome', false)
            ->where('landingPages.0.ctasCount', 3)
            ->where('landingPages.0.publishedAt', $landingPage->published_at?->toISOString()));
});

test('the create screen renders the form component', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.landing.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('back/Landing/Form', false));
});

test('administrators and editors can store a landing page', function (string $role) {
    $user = userWithRole($role);

    $this->actingAs($user)
        ->post(route('admin.landing.store'), landingPayload())
        ->assertRedirect(route('admin.landing.index'));

    $page = LandingPage::query()->where('slug', 'nouvelle-page')->firstOrFail();

    expect($page->title)->toBe('Nouvelle page')
        ->and($page->ctas)->toHaveCount(2)
        ->and($page->ctas[1]['style'])->toBe('secondary')
        ->and($page->seo['title'])->toBe('Nouvelle page - Services')
        ->and($page->is_home)->toBeFalse()
        ->and($page->published_at)->toBeNull();
})->with(['admin', 'editor']);

test('the slug is normalized to kebab-case', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.landing.store'), landingPayload(['slug' => 'Ma Super  Page !']));

    expect(LandingPage::query()->where('slug', 'ma-super-page')->exists())->toBeTrue();
});

test('a duplicate slug is rejected on creation and on update', function () {
    LandingPage::factory()->create(['slug' => 'deja-prise']);
    $mine = LandingPage::factory()->create(['slug' => 'la-mienne']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.landing.store'), landingPayload(['slug' => 'deja-prise']))
        ->assertSessionHasErrors('slug');

    $this->actingAs($admin)
        ->put(route('admin.landing.update', $mine), landingPayload(['slug' => 'deja-prise']))
        ->assertSessionHasErrors('slug');

    expect($mine->fresh()->slug)->toBe('la-mienne');
});

test('invalid CTA payloads are rejected', function (array $ctas, string $key) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.landing.store'), landingPayload(['ctas' => $ctas]))
        ->assertSessionHasErrors($key);
})->with([
    'bad url' => [
        [['label' => 'Ko', 'url' => 'pas-une-url', 'style' => 'primary']],
        'ctas.0.url',
    ],
    'bad style' => [
        [['label' => 'Ko', 'url' => 'https://services.test/x', 'style' => 'danger']],
        'ctas.0.style',
    ],
    'missing label' => [
        [['url' => 'https://services.test/x', 'style' => 'primary']],
        'ctas.0.label',
    ],
]);

test('an oversized seo description is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.landing.store'), landingPayload([
            'seo' => ['title' => 'Titre', 'description' => Str::repeat('a', 501)],
        ]))
        ->assertSessionHasErrors('seo.description');
});

test('a missing title is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.landing.store'), landingPayload(['title' => null]))
        ->assertSessionHasErrors('title');
});

test('the edit screen receives the landing page props', function () {
    $admin = User::factory()->admin()->create();

    $landingPage = LandingPage::factory()->create(['slug' => 'a-editer']);

    $this->actingAs($admin)
        ->get(route('admin.landing.edit', $landingPage))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Landing/Form', false)
            ->where('landingPage.slug', 'a-editer')
            ->where('landingPage.title', $landingPage->title)
            ->where('landingPage.heroTitle', $landingPage->hero_title)
            ->where('landingPage.heroSubtitle', $landingPage->hero_subtitle)
            ->has('landingPage.ctas', 2)
            ->where('landingPage.ctas.0.style', 'primary')
            ->has('landingPage.seo.title')
            ->has('landingPage.seo.description')
            ->where('landingPage.isHome', false)
            ->where('landingPage.publishedAt', null));
});

test('editors can update a landing page', function () {
    $editor = User::factory()->create();

    $page = LandingPage::factory()->create(['slug' => 'ancienne']);

    $this->actingAs($editor)
        ->put(route('admin.landing.update', $page), landingPayload(['slug' => 'nouvelle-edition']))
        ->assertRedirect(route('admin.landing.index'));

    $fresh = $page->fresh();

    expect($fresh->slug)->toBe('nouvelle-edition')
        ->and($fresh->published_at)->toBeNull();
});

test('updating keeps the publication state untouched', function () {
    $admin = User::factory()->admin()->create();

    $page = LandingPage::factory()->published()->create(['slug' => 'deja-publiee']);

    $this->actingAs($admin)
        ->put(route('admin.landing.update', $page), landingPayload(['slug' => 'toujours-publiee']))
        ->assertRedirect(route('admin.landing.index'));

    expect($page->fresh()->published_at)->not->toBeNull();
});

test('publishing toggles the publication of a page', function () {
    $editor = User::factory()->create();

    $page = LandingPage::factory()->create();

    $this->actingAs($editor)
        ->patch(route('admin.landing.publish', $page))
        ->assertRedirect(route('admin.landing.index'));

    expect($page->fresh()->published_at)->not->toBeNull();

    $this->actingAs($editor)->patch(route('admin.landing.publish', $page));

    expect($page->fresh()->published_at)->toBeNull();
});

test('flagging a new home page clears the previous one on creation and update', function () {
    $admin = User::factory()->admin()->create();

    $currentHome = LandingPage::factory()->home()->published()->create();

    $this->actingAs($admin)
        ->post(route('admin.landing.store'), landingPayload(['is_home' => true]));

    expect($currentHome->fresh()->is_home)->toBeFalse()
        ->and(LandingPage::query()->where('is_home', true)->count())->toBe(1);

    $challenger = LandingPage::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.landing.update', $challenger), landingPayload(['slug' => 'challenger', 'is_home' => true]));

    expect($currentHome->fresh()->is_home)->toBeFalse()
        ->and($challenger->fresh()->is_home)->toBeTrue()
        ->and(LandingPage::query()->where('is_home', true)->count())->toBe(1);
});

test('editors can delete a landing page', function () {
    $editor = User::factory()->create();

    $page = LandingPage::factory()->create();

    $this->actingAs($editor)
        ->delete(route('admin.landing.destroy', $page))
        ->assertRedirect(route('admin.landing.index'));

    expect(LandingPage::query()->count())->toBe(0);
});

test('deleting the home page leaves the platform without a home', function () {
    $admin = User::factory()->admin()->create();

    $home = LandingPage::factory()->home()->published()->create();

    $this->actingAs($admin)->delete(route('admin.landing.destroy', $home));

    expect(LandingPage::query()->where('is_home', true)->count())->toBe(0);

    $this->get('/')->assertNotFound();
});

test('disabling the module makes the admin routes 404', function () {
    $admin = User::factory()->admin()->create();

    Module::query()->where('slug', 'landing')->update(['enabled' => false]);

    $this->actingAs($admin)->get(route('admin.landing.index'))->assertNotFound();

    $this->actingAs($admin)
        ->post(route('admin.landing.store'), landingPayload())
        ->assertNotFound();
});
