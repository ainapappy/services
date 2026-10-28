<?php

namespace App\Domain\Monitoring\Http\Controllers\Admin;

use App\Domain\Monitoring\Actions\ResolveMonitorAlert;
use App\Domain\Monitoring\Models\MonitorAlert;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class AlertController extends Controller
{
    /**
     * Resolve an alert from the dashboard. The action is idempotent: an
     * already resolved alert keeps its original resolution date.
     */
    public function resolve(MonitorAlert $monitorAlert, ResolveMonitorAlert $action): RedirectResponse
    {
        $action->handle($monitorAlert);

        return redirect()->route('admin.monitoring.dashboard');
    }
}
