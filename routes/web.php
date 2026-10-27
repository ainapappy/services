<?php

use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Front-office
|--------------------------------------------------------------------------
*/

Route::inertia('/', 'front/Home')->name('home');

// Public registration is disabled: an explicit, clear message is shown on
// the login screen instead of an account creation form.
Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
});

/*
|--------------------------------------------------------------------------
| Back-office
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified'])
    ->group(function (): void {
        Route::inertia('/', 'back/Dashboard')->name('dashboard');

        Route::middleware('can:manage-modules')->group(function (): void {
            Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
            Route::patch('modules/{slug}/toggle', [ModuleController::class, 'toggle'])
                ->where('slug', '[a-z0-9-]+')
                ->name('modules.toggle');
        });

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    });
