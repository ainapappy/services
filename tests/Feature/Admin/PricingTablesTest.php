<?php

use App\Domain\Pricing\Actions\SyncTablePlans;
use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'pricing')->update(['enabled' => true]);
    $this->withoutVite();
});

function tablePayload(array $overrides = []): array
{
    return array_merge([
        'slug' => 'table-principale',
        'title' => 'Tarifs',
        'subtitle' => 'Choisissez la formule qui vous ressemble.',
        'faq' => [
            ['question' => 'Puis-je changer de formule ?', 'answer' => 'Oui, à tout moment.'],
            ['question' => 'Y a-t-il un engagement ?', 'answer' => 'Aucun engagement.'],
        ],
        'plan_ids' => [],
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.pricing.tables.index'))->assertRedirect(route('login'));
});

test('editors and administrators can visit the tables index', function (string $role) {
    $user = $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.pricing.tables.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Pricing/Tables', false)
            ->where('modules.1.slug', 'pricing')
            ->where('modules.1.enabled', true));
})->with(['admin', 'editor']);

test('the tables index receives the table props', function () {
    $admin = User::factory()->admin()->create();

    $plans = PricingPlan::factory()->count(2)->create();
    $table = PricingTable::factory()->create([
        'slug' => 'table-visible',
        'title' => 'Table visible',
        'faq' => [
            ['question' => 'Q1 ?', 'answer' => 'A1'],
            ['question' => 'Q2 ?', 'answer' => 'A2'],
            ['question' => 'Q3 ?', 'answer' => 'A3'],
        ],
    ]);
    (new SyncTablePlans)->handle($table, $plans->modelKeys());

    $this->actingAs($admin)
        ->get(route('admin.pricing.tables.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Pricing/Tables', false)
            ->has('tables', 1)
            ->where('tables.0.id', $table->id)
            ->where('tables.0.slug', 'table-visible')
            ->where('tables.0.title', 'Table visible')
            ->where('tables.0.subtitle', $table->subtitle)
            ->where('tables.0.publishedAt', null)
            ->where('tables.0.plansCount', 2)
            ->where('tables.0.faqCount', 3));
});

test('the create screen renders the table form with the full plan list', function () {
    $admin = User::factory()->admin()->create();
    $activePlan = PricingPlan::factory()->active()->create(['name' => 'Pro', 'sort' => 1]);
    $inactivePlan = PricingPlan::factory()->create(['name' => 'Agence', 'sort' => 2]);

    $this->actingAs($admin)
        ->get(route('admin.pricing.tables.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Pricing/TableForm', false)
            ->has('plans', 2)
            ->where('plans.0.id', $activePlan->id)
            ->where('plans.0.name', 'Pro')
            ->where('plans.0.active', true)
            ->where('plans.1.id', $inactivePlan->id)
            ->where('plans.1.name', 'Agence')
            ->where('plans.1.active', false)
            ->missing('table'));
});

test('administrators and editors can store a table', function (string $role) {
    $user = $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();

    $plans = PricingPlan::factory()->count(3)->create();

    $this->actingAs($user)
        ->post(route('admin.pricing.tables.store'), tablePayload([
            'plan_ids' => [$plans[2]->id, $plans[0]->id],
        ]))
        ->assertRedirect(route('admin.pricing.tables.index'));

    $table = PricingTable::query()->where('slug', 'table-principale')->firstOrFail();

    expect($table->title)->toBe('Tarifs')
        ->and($table->subtitle)->toBe('Choisissez la formule qui vous ressemble.')
        ->and($table->faq)->toHaveCount(2)
        ->and($table->faq[1]['answer'])->toBe('Aucun engagement.')
        ->and($table->published_at)->toBeNull()
        // The submitted order wins, not the ids' natural order.
        ->and($table->plans->pluck('id')->all())->toBe([$plans[2]->id, $plans[0]->id])
        ->and($table->plans[0]->pivot->sort)->toBe(0)
        ->and($table->plans[1]->pivot->sort)->toBe(1);
})->with(['admin', 'editor']);

test('the edit screen receives the table props', function () {
    $admin = User::factory()->admin()->create();

    $plans = PricingPlan::factory()->count(2)->create();
    $table = PricingTable::factory()->published()->create(['slug' => 'a-editer']);
    (new SyncTablePlans)->handle($table, [$plans[1]->id, $plans[0]->id]);

    $this->actingAs($admin)
        ->get(route('admin.pricing.tables.edit', $table))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Pricing/TableForm', false)
            ->where('table.slug', 'a-editer')
            ->where('table.title', $table->title)
            ->where('table.subtitle', $table->subtitle)
            ->has('table.faq', 2)
            ->where('table.faq.0.question', 'Question one?')
            ->where('table.faq.0.answer', 'Answer one.')
            ->where('table.publishedAt', $table->published_at?->toISOString())
            ->where('table.planIds.0', $plans[1]->id)
            ->where('table.planIds.1', $plans[0]->id)
            ->has('plans', 2));
});

test('editors can update a table and the plan order is resynced', function () {
    $editor = User::factory()->create();

    $initial = PricingPlan::factory()->count(2)->create();
    $replacement = PricingPlan::factory()->create();
    $table = PricingTable::factory()->create(['slug' => 'ancienne']);
    (new SyncTablePlans)->handle($table, [$initial[0]->id, $initial[1]->id]);

    $this->actingAs($editor)
        ->put(route('admin.pricing.tables.update', $table), tablePayload([
            'slug' => 'nouvelle-edition',
            'plan_ids' => [$replacement->id, $initial[0]->id],
        ]))
        ->assertRedirect(route('admin.pricing.tables.index'));

    $fresh = $table->fresh();

    expect($fresh->slug)->toBe('nouvelle-edition')
        ->and($fresh->published_at)->toBeNull()
        ->and($fresh->plans->pluck('id')->all())->toBe([$replacement->id, $initial[0]->id])
        ->and($fresh->plans[0]->pivot->sort)->toBe(0)
        ->and($fresh->plans[1]->pivot->sort)->toBe(1);
});

test('updating keeps the publication state untouched', function () {
    $admin = User::factory()->admin()->create();

    $table = PricingTable::factory()->published()->create(['slug' => 'deja-publiee']);

    $this->actingAs($admin)
        ->put(route('admin.pricing.tables.update', $table), tablePayload(['slug' => 'toujours-publiee']))
        ->assertRedirect(route('admin.pricing.tables.index'));

    expect($table->fresh()->published_at)->not->toBeNull();
});

test('invalid faq payloads are rejected', function (mixed $faq, string $key) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.tables.store'), tablePayload(['faq' => $faq]))
        ->assertSessionHasErrors($key);
})->with([
    'empty question' => [[['question' => '', 'answer' => 'A']], 'faq.0.question'],
    'whitespace answer' => [[['question' => 'Q ?', 'answer' => '   ']], 'faq.0.answer'],
    'missing answer' => [[['question' => 'Q ?']], 'faq.0.answer'],
    'faq entry not an object' => [['texte brut'], 'faq.0.question'],
    'faq not an array' => ['texte', 'faq'],
]);

test('an unknown plan id is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.tables.store'), tablePayload(['plan_ids' => [999]]))
        ->assertSessionHasErrors('plan_ids.0');
});

test('a duplicate table slug is rejected', function () {
    PricingTable::factory()->create(['slug' => 'deja-prise']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.pricing.tables.store'), tablePayload(['slug' => 'deja-prise']))
        ->assertSessionHasErrors('slug');
});

test('publishing toggles the publication of a table', function () {
    $editor = User::factory()->create();

    $table = PricingTable::factory()->create();

    $this->actingAs($editor)
        ->patch(route('admin.pricing.tables.publish', $table))
        ->assertRedirect(route('admin.pricing.tables.index'));

    expect($table->fresh()->published_at)->not->toBeNull();

    $this->actingAs($editor)->patch(route('admin.pricing.tables.publish', $table));

    expect($table->fresh()->published_at)->toBeNull();
});

test('editors can delete a table', function () {
    $editor = User::factory()->create();

    $table = PricingTable::factory()->create();

    $this->actingAs($editor)
        ->delete(route('admin.pricing.tables.destroy', $table))
        ->assertRedirect(route('admin.pricing.tables.index'));

    expect(PricingTable::query()->count())->toBe(0);
});

test('disabling the module makes the table admin routes 404', function () {
    $admin = User::factory()->admin()->create();

    Module::query()->where('slug', 'pricing')->update(['enabled' => false]);

    $this->actingAs($admin)->get(route('admin.pricing.tables.index'))->assertNotFound();

    $this->actingAs($admin)
        ->post(route('admin.pricing.tables.store'), tablePayload())
        ->assertNotFound();
});
