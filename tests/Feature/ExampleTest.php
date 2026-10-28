<?php

use App\Domain\Landing\Models\LandingPage;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;

test('returns a successful response', function () {
    // The front assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'landing')->update(['enabled' => true]);
    LandingPage::factory()->home()->published()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
});
