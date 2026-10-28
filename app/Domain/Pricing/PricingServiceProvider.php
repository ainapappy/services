<?php

namespace App\Domain\Pricing;

use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use App\Domain\Pricing\Policies\PricingPlanPolicy;
use App\Domain\Pricing\Policies\PricingTablePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PricingServiceProvider extends ServiceProvider
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
     *
     * The back-office routes live in the domain; the public "/pricing" page
     * stays in routes/web.php, before the "/{slug}" capture route which must
     * be registered after every static route.
     */
    public function boot(): void
    {
        Gate::policy(PricingPlan::class, PricingPlanPolicy::class);
        Gate::policy(PricingTable::class, PricingTablePolicy::class);

        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');
    }
}
