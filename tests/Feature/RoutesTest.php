<?php

use App\Models\User;

test('the front-office home page returns a successful response', function () {
    // The front assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();

    $this->get(route('home'))->assertOk();
});

test('the back-office dashboard is reserved to verified users', function () {
    // The back assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});

test('guests hitting the back-office root are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
