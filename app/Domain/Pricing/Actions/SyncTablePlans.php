<?php

namespace App\Domain\Pricing\Actions;

use App\Domain\Pricing\Models\PricingTable;
use Illuminate\Support\Facades\DB;

class SyncTablePlans
{
    /**
     * Attach the given plans to the table in the given order (the first id
     * gets sort 0) and detach every currently attached plan that is missing
     * from the list. The form requests already validated that every id
     * exists, and duplicates collapse to their first occurrence.
     *
     * @param  list<int>  $planIds
     */
    public function handle(PricingTable $pricingTable, array $planIds): void
    {
        $pivot = collect($planIds)
            ->mapWithKeys(fn (int $planId, int $index): array => [$planId => ['sort' => $index]])
            ->all();

        DB::transaction(function () use ($pricingTable, $pivot): void {
            $pricingTable->plans()->sync($pivot);
        });
    }
}
