<?php

use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.modules.index'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $admin = User::factory()->unverified()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.modules.index'))
        ->assertRedirect(route('verification.notice'));
});

test('editors are forbidden from the modules screen', function () {
    $editor = User::factory()->create();

    $this->actingAs($editor)
        ->get(route('admin.modules.index'))
        ->assertForbidden();
});

test('editors are forbidden from toggling a module', function () {
    $editor = User::factory()->create();

    $this->actingAs($editor)
        ->patch(route('admin.modules.toggle', ['slug' => 'gateway']))
        ->assertForbidden();

    expect(Module::isEnabled('gateway'))->toBeFalse();
});

test('administrators see the modules screen with every registry module', function () {
    $this->withoutVite();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.modules.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Modules', false)
            ->has('modules', 6)
            ->where('modules.0.slug', 'landing')
            ->where('modules.0.enabled', false)
            ->where('modules.2.slug', 'gateway')
            ->where('modules.2.enabled', false));
});

test('administrators can enable a module', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.modules.toggle', ['slug' => 'gateway']))
        ->assertRedirect(route('admin.modules.index'));

    expect(Module::isEnabled('gateway'))->toBeTrue();
});

test('administrators can disable a module', function () {
    Module::query()->where('slug', 'gateway')->update(['enabled' => true]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.modules.toggle', ['slug' => 'gateway']))
        ->assertRedirect(route('admin.modules.index'));

    expect(Module::isEnabled('gateway'))->toBeFalse();
});

test('toggling persists and immediately cuts the module routes', function () {
    Route::get('/probe/gateway', fn (): string => 'gateway content')
        ->middleware('module:gateway');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.modules.toggle', ['slug' => 'gateway']));
    $this->get('/probe/gateway')->assertOk()->assertSee('gateway content');

    $this->actingAs($admin)->patch(route('admin.modules.toggle', ['slug' => 'gateway']));
    $this->get('/probe/gateway')->assertNotFound();

    expect(Module::isEnabled('gateway'))->toBeFalse();
});

test('toggling a slug missing from the registry returns 404', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.modules.toggle', ['slug' => 'unknown']))
        ->assertNotFound();
});
