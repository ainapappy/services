<?php

namespace App\Domain\Pricing\Http\Controllers\Admin;

use App\Domain\Pricing\Actions\CreatePlan;
use App\Domain\Pricing\Actions\DeletePlan;
use App\Domain\Pricing\Actions\TogglePlan;
use App\Domain\Pricing\Actions\UpdatePlan;
use App\Domain\Pricing\Http\Requests\CreatePlanRequest;
use App\Domain\Pricing\Http\Requests\UpdatePlanRequest;
use App\Domain\Pricing\Models\PricingPlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    /**
     * Display the pricing plans list.
     */
    public function index(): Response
    {
        $plans = PricingPlan::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->map(fn (PricingPlan $plan): array => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'tagline' => $plan->tagline,
                'priceMonthly' => $plan->price_monthly,
                'priceYearly' => $plan->price_yearly,
                'currency' => $plan->currency,
                'features' => $plan->features,
                'popular' => $plan->popular,
                'active' => $plan->active,
                'sort' => $plan->sort,
            ])
            ->values()
            ->all();

        return Inertia::render('back/Pricing/Plans', ['plans' => $plans]);
    }

    /**
     * Display the pricing plan creation form.
     */
    public function create(): Response
    {
        return Inertia::render('back/Pricing/PlanForm');
    }

    /**
     * Store a new pricing plan.
     */
    public function store(CreatePlanRequest $request, CreatePlan $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('admin.pricing.plans.index');
    }

    /**
     * Display the pricing plan edition form.
     */
    public function edit(PricingPlan $plan): Response
    {
        return Inertia::render('back/Pricing/PlanForm', [
            'plan' => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'tagline' => $plan->tagline,
                'priceMonthly' => $plan->price_monthly,
                'priceYearly' => $plan->price_yearly,
                'currency' => $plan->currency,
                'features' => $plan->features,
                'popular' => $plan->popular,
                'active' => $plan->active,
                'sort' => $plan->sort,
            ],
        ]);
    }

    /**
     * Update a pricing plan.
     */
    public function update(UpdatePlanRequest $request, PricingPlan $plan, UpdatePlan $action): RedirectResponse
    {
        $action->handle($plan, $request->validated());

        return redirect()->route('admin.pricing.plans.index');
    }

    /**
     * Toggle the activation of a pricing plan.
     */
    public function toggle(PricingPlan $plan, TogglePlan $action): RedirectResponse
    {
        $action->handle($plan);

        return redirect()->route('admin.pricing.plans.index');
    }

    /**
     * Delete a pricing plan.
     */
    public function destroy(PricingPlan $plan, DeletePlan $action): RedirectResponse
    {
        $action->handle($plan);

        return redirect()->route('admin.pricing.plans.index');
    }
}
