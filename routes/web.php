<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::inertia('/', 'front/Home')->name('home');
});

Route::middleware('web')
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::inertia('/', 'back/Home')->name('home');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
