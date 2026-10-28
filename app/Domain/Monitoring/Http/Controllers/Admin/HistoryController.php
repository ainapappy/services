<?php

namespace App\Domain\Monitoring\Http\Controllers\Admin;

use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorCheck;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    /**
     * The widest period the "days" filter accepts, so a typo never
     * loads the whole journal.
     */
    final public const MAX_FILTER_DAYS = 365;

    /**
     * Display the checks journal, filterable by monitor type, check
     * status and period (days). The filter values travel in the query
     * string (type, status, days); unknown values are ignored rather
     * than rejected, so a stale filter never breaks the page, and only
     * the applied values are echoed back to the UI.
     */
    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString();
        $status = $request->string('status')->toString();
        $days = $request->integer('days');

        $appliedType = in_array($type, Monitor::TYPES, true) ? $type : null;
        $appliedStatus = in_array($status, MonitorCheck::STATUSES, true) ? $status : null;
        $appliedDays = $days > 0 && $days <= self::MAX_FILTER_DAYS ? $days : null;

        $checks = MonitorCheck::query()
            ->with('monitor:id,name,type')
            ->when($appliedType !== null, fn (Builder $query): Builder => $query->whereHas(
                'monitor',
                fn (Builder $monitorQuery): Builder => $monitorQuery->where('type', $appliedType),
            ))
            ->when($appliedStatus !== null, fn (Builder $query): Builder => $query->where('status', $appliedStatus))
            ->when($appliedDays !== null, fn (Builder $query): Builder => $query->where(
                'checked_at',
                '>=',
                now()->subDays($appliedDays),
            ))
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MonitorCheck $check): array => [
                'id' => $check->id,
                'monitorName' => $check->monitor->name,
                'type' => $check->monitor->type,
                'status' => $check->status,
                'latencyMs' => $check->latency_ms,
                'message' => $check->message,
                'checkedAt' => $check->checked_at->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Monitoring/History', [
            'checks' => $checks,
            'filters' => [
                'type' => $appliedType,
                'status' => $appliedStatus,
                'days' => $appliedDays,
            ],
        ]);
    }
}
