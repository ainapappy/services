<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.profile.edit'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('admin.profile.edit'))
        ->assertRedirect(route('verification.notice'));
});

test('profile page is displayed', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Profile', false)
            ->where('mustVerifyEmail', true)
            ->where('auth.user.email', $user->email)
            ->where('auth.user.role', 'editor'));
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put(route('admin.profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put(route('admin.profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('email must be unique across users', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('admin.profile.edit'))
        ->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('admin.profile.edit'))
        ->put(route('admin.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.profile.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('admin.profile.edit'))
        ->put(route('admin.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('admin.profile.edit'));

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
