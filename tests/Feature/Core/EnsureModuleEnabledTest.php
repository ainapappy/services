<?php

use App\Models\Module;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\Route;

test('the module middleware returns 404 when the module is disabled', function () {
    $this->seed(ModuleSeeder::class);

    Route::get('/probe/gateway', fn (): string => 'gateway content')
        ->middleware('module:gateway');

    $this->get('/probe/gateway')->assertNotFound();
});

test('the module middleware returns 200 when the module is enabled', function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'gateway')->update(['enabled' => true]);

    Route::get('/probe/gateway', fn (): string => 'gateway content')
        ->middleware('module:gateway');

    $this->get('/probe/gateway')->assertOk()->assertSee('gateway content');
});

test('the module middleware returns 404 for a slug missing from the registry', function () {
    Route::get('/probe/unknown', fn (): string => 'never rendered')
        ->middleware('module:unknown');

    $this->get('/probe/unknown')->assertNotFound();
});

test('the module middleware returns 404 when the module row is missing', function () {
    Route::get('/probe/landing', fn (): string => 'never rendered')
        ->middleware('module:landing');

    $this->get('/probe/landing')->assertNotFound();
});
