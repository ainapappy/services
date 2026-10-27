<?php

test('returns a successful response', function () {
    // The front assets are built by the parallel frontend workflow; rendering
    // must not depend on the current state of the Vite manifest.
    $this->withoutVite();

    $response = $this->get(route('home'));

    $response->assertOk();
});
