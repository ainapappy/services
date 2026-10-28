<?php

use App\Domain\Landing\Models\LandingPage;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;

test('the front-office root answers 404 without a published home landing page', function () {
    // The front assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'landing')->update(['enabled' => true]);

    $this->get(route('home'))->assertNotFound();
});

test('the front-office root renders the published home landing page', function () {
    // The front assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'landing')->update(['enabled' => true]);

    LandingPage::factory()->home()->published()->create();

    $this->get(route('home'))->assertOk();
});

test('the back-office dashboard is reserved to verified users', function () {
    // The front assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});

test('guests hitting the back-office root are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
