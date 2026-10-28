<?php

use App\Domain\Pricing\Actions\PublishTable;
use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'pricing')->update(['enabled' => true]);
    $this->withoutVite();
});

test('the pricing page answers 404 when no table is published', function () {
    PricingTable::factory()->create();

    $this->get(route('pricing'))->assertNotFound();
});

test('the pricing page answers 404 when the table is not published', function () {
    PricingTable::factory()->withPlans(2)->create(['slug' => 'brouillon']);

    $this->get(route('pricing'))->assertNotFound();
});

test('the pricing page renders the published table', function () {
    $table = PricingTable::factory()->published()->withPlans(2)->create([
        'slug' => 'tarifs',
        'title' => 'Tarifs',
        'subtitle' => 'Choisissez votre formule.',
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->where('table.slug', 'tarifs')
            ->where('table.title', 'Tarifs')
            ->where('table.subtitle', 'Choisissez votre formule.')
            ->has('table.plans', 2)
            ->has('table.faq', 2));
});

test('the pricing page receives both prices in cents for every plan', function () {
    $plan = PricingPlan::factory()->active()->create([
        'slug' => 'plan-prix',
        'price_monthly' => 1900,
        'price_yearly' => 19000,
    ]);
    $table = PricingTable::factory()->published()->create();
    $table->plans()->attach($plan, ['sort' => 0]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->where('table.plans.0.slug', 'plan-prix')
            ->where('table.plans.0.priceMonthly', 1900)
            ->where('table.plans.0.priceYearly', 19000)
            ->where('table.plans.0.currency', 'EUR')
            ->has('table.plans.0.features', 3));
});

test('the pricing page renders plans in pivot order', function () {
    $first = PricingPlan::factory()->active()->create(['slug' => 'plan-c']);
    $second = PricingPlan::factory()->active()->create(['slug' => 'plan-a']);
    $third = PricingPlan::factory()->active()->create(['slug' => 'plan-b']);
    $table = PricingTable::factory()->published()->create();
    $table->plans()->sync([
        $first->id => ['sort' => 0],
        $second->id => ['sort' => 1],
        $third->id => ['sort' => 2],
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->where('table.plans.0.slug', 'plan-c')
            ->where('table.plans.1.slug', 'plan-a')
            ->where('table.plans.2.slug', 'plan-b'));
});

test('the pricing page only renders active plans', function () {
    $active = PricingPlan::factory()->active()->create(['slug' => 'plan-actif']);
    $hidden = PricingPlan::factory()->create(['slug' => 'plan-cache']);
    $table = PricingTable::factory()->published()->create();
    $table->plans()->sync([
        $active->id => ['sort' => 0],
        $hidden->id => ['sort' => 1],
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->has('table.plans', 1)
            ->where('table.plans.0.slug', 'plan-actif'));
});

test('the popular plan is rendered as popular', function () {
    $star = PricingPlan::factory()->active()->popular()->create(['slug' => 'plan-star']);
    $table = PricingTable::factory()->published()->create();
    $table->plans()->attach($star, ['sort' => 0]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->where('table.plans.0.popular', true));
});

test('the table query selects a specific published table', function () {
    PricingTable::factory()->published()->withPlans(1)->create(['slug' => 'premiere']);
    $second = PricingTable::factory()->published()->withPlans(1)->create(['slug' => 'seconde']);

    $this->get(route('pricing', ['table' => 'seconde']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->where('table.slug', 'seconde')
            ->where('table.title', $second->title));
});

test('an unknown table slug answers 404', function () {
    PricingTable::factory()->published()->create(['slug' => 'tarifs']);

    $this->get(route('pricing', ['table' => 'n-importe-quoi']))->assertNotFound();
});

test('an unpublished table slug answers 404', function () {
    PricingTable::factory()->published()->create(['slug' => 'tarifs']);
    PricingTable::factory()->create(['slug' => 'brouillon']);

    $this->get(route('pricing', ['table' => 'brouillon']))->assertNotFound();
});

test('without the table query the first published table is shown', function () {
    PricingTable::factory()->published()->withPlans(1)->create([
        'slug' => 'ancienne',
        'published_at' => now()->subDay(),
    ]);
    PricingTable::factory()->published()->withPlans(1)->create(['slug' => 'recente']);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->where('table.slug', 'ancienne'));
});

test('the pricing page passes the edited faq to the front', function () {
    PricingTable::factory()->published()->create([
        'slug' => 'tarifs-faq',
        'faq' => [
            ['question' => 'Puis-je changer ?', 'answer' => 'À tout moment.'],
            ['question' => 'Un engagement ?', 'answer' => 'Aucun.'],
        ],
    ]);

    $this->get(route('pricing', ['table' => 'tarifs-faq']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('front/Pricing/Show', false)
            ->has('table.faq', 2)
            ->where('table.faq.0.question', 'Puis-je changer ?')
            ->where('table.faq.0.answer', 'À tout moment.')
            ->where('table.faq.1.question', 'Un engagement ?')
            ->where('table.faq.1.answer', 'Aucun.'));
});

test('unpublishing a table makes the page 404 again', function () {
    $table = PricingTable::factory()->withPlans(2)->create(['slug' => 'tarifs']);

    $this->get(route('pricing'))->assertNotFound();

    (new PublishTable)->handle($table);

    $this->get(route('pricing'))->assertOk();

    (new PublishTable)->handle($table);

    $this->get(route('pricing'))->assertNotFound();
});

test('disabling the module makes the pricing page 404', function () {
    PricingTable::factory()->published()->withPlans(2)->create(['slug' => 'tarifs']);

    Module::query()->where('slug', 'pricing')->update(['enabled' => false]);

    $this->get(route('pricing'))->assertNotFound();
    $this->get(route('pricing', ['table' => 'tarifs']))->assertNotFound();
});
