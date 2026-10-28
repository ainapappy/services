<?php

use App\Models\User;

test('the registration screen redirects to the login screen with an explicit message', function () {
    $response = $this->get(route('register'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas(
            'status',
            __('Public registration is disabled. Ask an administrator to create an account for you.'),
        );
});

test('registration cannot create an account', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('login'));

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('authenticated users hitting the registration screen land on the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('register'))
        ->assertRedirect(route('admin.dashboard'));
});
