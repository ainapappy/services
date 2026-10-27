<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin:create creates a verified administrator', function () {
    $this->artisan('admin:create', [
        'name' => 'Admin',
        'email' => 'admin@services.test',
        '--password' => 'secret-password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@services.test')->firstOrFail();

    expect($user->role)->toBe('admin');
    expect($user->isAdmin())->toBeTrue();
    expect($user->hasVerifiedEmail())->toBeTrue();
    expect(Hash::check('secret-password', $user->password))->toBeTrue();
});

test('admin:create refuses a duplicate email', function () {
    User::factory()->create(['email' => 'admin@services.test']);

    $this->artisan('admin:create', [
        'name' => 'Admin',
        'email' => 'admin@services.test',
        '--password' => 'secret-password',
    ])->assertFailed();

    expect(User::query()->where('email', 'admin@services.test')->count())->toBe(1);
});

test('admin:create is case-insensitive on duplicate emails', function () {
    User::factory()->create(['email' => 'Admin@Services.test']);

    $this->artisan('admin:create', [
        'name' => 'Admin',
        'email' => 'admin@services.test',
    ])->assertFailed();

    expect(User::query()->where('email', 'Admin@Services.test')->count())->toBe(1);
});

test('admin:create generates a strong password when the option is omitted', function () {
    $this->artisan('admin:create', [
        'name' => 'Admin',
        'email' => 'admin@services.test',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@services.test')->firstOrFail();

    expect($user->role)->toBe('admin');
    expect(Hash::check('password', $user->password))->toBeFalse();
});
