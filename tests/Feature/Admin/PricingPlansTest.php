<?php

use App\Domain\Pricing\Models\PricingPlan;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'pricing')->update(['enabled' => true]);
    $this->withoutVite();
});

function planPayload(array $overrides = []): array
{
    return array_merge([
        'slug' => 'plan-pro',
        'name' => 'Pro',
        'tagline' => 'Pour les freelances.',
        'price_monthly' => 2900,
        'price_yearly' => 29000,
        'currency' => 'EUR',
        'features' => ['Inclus A', 'Inclus B'],
        'popular' => false,
        'active' => false,
        'sort' => 0,
    ], $overrides);
}

function pricingUserWithRole(string $role): User
{
    return $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.pricing.plans.index'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $admin = User::factory()->unverified()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.pricing.plans.index'))
        ->assertRedirect(route('verification.notice'));
});

test('editors and administrators can visit the plans index', function (string $role) {
    $user = pricingUserWithRole($role);

    $this->actingAs($user)
        ->get(route('admin.pricing.plans.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Pricing/Plans', false)
            ->where('modules.1.slug', 'pricing')
            ->where('modules.1.enabled', true));
})->with(['admin', 'editor']);

test('the plans index receives the plan props', function () {
    $admin = User::factory()->admin()->create();

    $plan = PricingPlan::factory()->active()->popular()->create([
        'slug' => 'plan-visible',
        'name' => 'Pro',
        'tagline' => 'Pour les freelances.',
        'price_monthly' => 2900,
        'price_yearly' => 29000,
        'features' => ['Inclus A', 'Inclus B'],
        'sort' => 4,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.pricing.plans.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Pricing/Plans', false)
            ->has('plans', 1)
            ->where('plans.0.id', $plan->id)
            ->where('plans.0.slug', 'plan-visible')
            ->where('plans.0.name', 'Pro')
            ->where('plans.0.tagline', 'Pour les freelances.')
            ->where('plans.0.priceMonthly', 2900)
            ->where('plans.0.priceYearly', 29000)
            ->where('plans.0.currency', 'EUR')
            ->has('plans.0.features', 2)
            ->where('plans.0.popular', true)
            ->where('plans.0.active', true)
            ->where('plans.0.sort', 4));
});

test('the create screen renders the plan form component', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.pricing.plans.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('back/Pricing/PlanForm', false));
});

test('administrators and editors can store a plan', function (string $role) {
    $user = pricingUserWithRole($role);

    $this->actingAs($user)
        ->post(route('admin.pricing.plans.store'), planPayload())
        ->assertRedirect(route('admin.pricing.plans.index'));

    $plan = PricingPlan::query()->where('slug', 'plan-pro')->firstOrFail();

    expect($plan->name)->toBe('Pro')
        ->and($plan->tagline)->toBe('Pour les freelances.')
        ->and($plan->price_monthly)->toBe(2900)
        ->and($plan->price_yearly)->toBe(29000)
        ->and($plan->currency)->toBe('EUR')
        ->and($plan->features)->toBe(['Inclus A', 'Inclus B'])
        ->and($plan->popular)->toBeFalse()
        ->and($plan->active)->toBeFalse()
        ->and($plan->sort)->toBe(0);
})->with(['admin', 'editor']);

test('the plan slug is normalized to kebab-case', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.plans.store'), planPayload(['slug' => 'Le Plan Pro !']));

    expect(PricingPlan::query()->where('slug', 'le-plan-pro')->exists())->toBeTrue();
});

test('a duplicate plan slug is rejected on creation and on update', function () {
    PricingPlan::factory()->create(['slug' => 'deja-prise']);
    $mine = PricingPlan::factory()->create(['slug' => 'la-mienne']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.plans.store'), planPayload(['slug' => 'deja-prise']))
        ->assertSessionHasErrors('slug');

    $this->actingAs($admin)
        ->put(route('admin.pricing.plans.update', $mine), planPayload(['slug' => 'deja-prise']))
        ->assertSessionHasErrors('slug');

    expect($mine->fresh()->slug)->toBe('la-mienne');
});

test('negative prices are rejected', function (string $key) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.plans.store'), planPayload([$key => -1]))
        ->assertSessionHasErrors($key);
})->with([
    'negative monthly price' => ['price_monthly'],
    'negative yearly price' => ['price_yearly'],
]);

test('non-integer prices are rejected', function (mixed $value) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.plans.store'), planPayload(['price_monthly' => $value]))
        ->assertSessionHasErrors('price_monthly');
})->with([
    'float price' => [1.5],
    'decimal string price' => ['12.50'],
    'text price' => ['douze'],
]);

test('invalid features payloads are rejected', function (mixed $features, string $key) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.plans.store'), planPayload(['features' => $features]))
        ->assertSessionHasErrors($key);
})->with([
    'empty feature string' => [['Inclus A', ''], 'features.1'],
    'whitespace feature' => [['   '], 'features.0'],
    'features not an array' => ['Inclus A', 'features'],
    'oversized feature' => [[str_repeat('a', 256)], 'features.0'],
]);

test('the edit screen receives the plan props', function () {
    $admin = User::factory()->admin()->create();

    $plan = PricingPlan::factory()->create(['slug' => 'a-editer']);

    $this->actingAs($admin)
        ->get(route('admin.pricing.plans.edit', $plan))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Pricing/PlanForm', false)
            ->where('plan.slug', 'a-editer')
            ->where('plan.name', $plan->name)
            ->where('plan.tagline', $plan->tagline)
            ->where('plan.priceMonthly', $plan->price_monthly)
            ->where('plan.priceYearly', $plan->price_yearly)
            ->where('plan.currency', 'EUR')
            ->has('plan.features', 3)
            ->where('plan.popular', false)
            ->where('plan.active', false)
            ->where('plan.sort', 0));
});

test('editors can update a plan', function () {
    $editor = User::factory()->create();

    $plan = PricingPlan::factory()->create(['slug' => 'ancien']);

    $this->actingAs($editor)
        ->put(route('admin.pricing.plans.update', $plan), planPayload([
            'slug' => 'nouvelle-edition',
            'price_monthly' => 4900,
            'price_yearly' => 49000,
            'sort' => 2,
        ]))
        ->assertRedirect(route('admin.pricing.plans.index'));

    $fresh = $plan->fresh();

    expect($fresh->slug)->toBe('nouvelle-edition')
        ->and($fresh->price_monthly)->toBe(4900)
        ->and($fresh->price_yearly)->toBe(49000)
        ->and($fresh->sort)->toBe(2)
        ->and($fresh->active)->toBeFalse();
});

test('updating keeps the active flag untouched', function () {
    $admin = User::factory()->admin()->create();

    $plan = PricingPlan::factory()->active()->create(['slug' => 'deja-actif']);

    $this->actingAs($admin)
        ->put(route('admin.pricing.plans.update', $plan), planPayload(['slug' => 'toujours-actif']))
        ->assertRedirect(route('admin.pricing.plans.index'));

    expect($plan->fresh()->active)->toBeTrue();
});

test('toggling flips the active flag', function () {
    $editor = User::factory()->create();

    $plan = PricingPlan::factory()->create();

    $this->actingAs($editor)
        ->patch(route('admin.pricing.plans.toggle', $plan))
        ->assertRedirect(route('admin.pricing.plans.index'));

    expect($plan->fresh()->active)->toBeTrue();

    $this->actingAs($editor)->patch(route('admin.pricing.plans.toggle', $plan));

    expect($plan->fresh()->active)->toBeFalse();
});

test('editors can delete a plan and the pivot rows go with it', function () {
    $editor = User::factory()->create();

    $plan = PricingPlan::factory()->create();

    $this->actingAs($editor)
        ->delete(route('admin.pricing.plans.destroy', $plan))
        ->assertRedirect(route('admin.pricing.plans.index'));

    expect(PricingPlan::query()->count())->toBe(0);
});

test('disabling the module makes the plan admin routes 404', function () {
    $admin = User::factory()->admin()->create();

    Module::query()->where('slug', 'pricing')->update(['enabled' => false]);

    $this->actingAs($admin)->get(route('admin.pricing.plans.index'))->assertNotFound();

    $this->actingAs($admin)
        ->post(route('admin.pricing.plans.store'), planPayload())
        ->assertNotFound();
});
