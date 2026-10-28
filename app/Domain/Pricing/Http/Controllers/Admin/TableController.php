<?php

namespace App\Domain\Pricing\Http\Controllers\Admin;

use App\Domain\Pricing\Actions\CreateTable;
use App\Domain\Pricing\Actions\DeleteTable;
use App\Domain\Pricing\Actions\PublishTable;
use App\Domain\Pricing\Actions\UpdateTable;
use App\Domain\Pricing\Http\Requests\CreateTableRequest;
use App\Domain\Pricing\Http\Requests\UpdateTableRequest;
use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    /**
     * Display the pricing tables list.
     */
    public function index(): Response
    {
        $tables = PricingTable::query()
            ->withCount('plans')
            ->orderBy('title')
            ->get()
            ->map(fn (PricingTable $table): array => [
                'id' => $table->id,
                'slug' => $table->slug,
                'title' => $table->title,
                'subtitle' => $table->subtitle,
                'publishedAt' => $table->published_at?->toISOString(),
                'plansCount' => $table->plans_count,
                'faqCount' => count($table->faq),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Pricing/Tables', ['tables' => $tables]);
    }

    /**
     * Display the pricing table creation form.
     */
    public function create(): Response
    {
        return Inertia::render('back/Pricing/TableForm', ['plans' => $this->selectablePlans()]);
    }

    /**
     * Store a new pricing table.
     */
    public function store(CreateTableRequest $request, CreateTable $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('admin.pricing.tables.index');
    }

    /**
     * Display the pricing table edition form.
     */
    public function edit(PricingTable $pricingTable): Response
    {
        return Inertia::render('back/Pricing/TableForm', [
            'table' => [
                'id' => $pricingTable->id,
                'slug' => $pricingTable->slug,
                'title' => $pricingTable->title,
                'subtitle' => $pricingTable->subtitle,
                'faq' => $pricingTable->faq,
                'publishedAt' => $pricingTable->published_at?->toISOString(),
                'planIds' => $pricingTable->plans->pluck('id')->values()->all(),
            ],
            'plans' => $this->selectablePlans(),
        ]);
    }

    /**
     * Update a pricing table.
     */
    public function update(UpdateTableRequest $request, PricingTable $pricingTable, UpdateTable $action): RedirectResponse
    {
        $action->handle($pricingTable, $request->validated());

        return redirect()->route('admin.pricing.tables.index');
    }

    /**
     * Publish or unpublish a pricing table.
     */
    public function publish(PricingTable $pricingTable, PublishTable $action): RedirectResponse
    {
        $action->handle($pricingTable);

        return redirect()->route('admin.pricing.tables.index');
    }

    /**
     * Delete a pricing table.
     */
    public function destroy(PricingTable $pricingTable, DeleteTable $action): RedirectResponse
    {
        $action->handle($pricingTable);

        return redirect()->route('admin.pricing.tables.index');
    }

    /**
     * Every plan, for the table form selection: the complete list, ordered
     * by the back-office sort.
     *
     * @return list<array{id: int, name: string, active: bool}>
     */
    private function selectablePlans(): array
    {
        $plans = [];

        foreach (PricingPlan::query()->orderBy('sort')->orderBy('name')->get() as $plan) {
            $plans[] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'active' => $plan->active,
            ];
        }

        return $plans;
    }
}
