<?php

namespace App\Domain\Landing;

use App\Domain\Landing\Models\LandingPage;
use App\Domain\Landing\Policies\LandingPagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LandingServiceProvider extends ServiceProvider
{
    /**
     * Register any domain services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any domain services.
     */
    public function boot(): void
    {
        Gate::policy(LandingPage::class, LandingPagePolicy::class);

        // The back-office routes live in the domain; the public ones ("home"
        // and the "/{slug}" capture route) stay in routes/web.php, because
        // route files load after the providers boot and the capture route
        // must be registered after every static route.
        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');
    }
}
