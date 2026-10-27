<?php

test('the front-office home page returns a successful response', function () {
    // The front assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();

    $this->get(route('home'))->assertOk();
});

test('the back-office home page returns a successful response', function () {
    $this->withoutVite();

    $this->get(route('admin.home'))->assertOk();
});
