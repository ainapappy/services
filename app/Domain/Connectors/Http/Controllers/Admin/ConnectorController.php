<?php

namespace App\Domain\Connectors\Http\Controllers\Admin;

use App\Domain\Connectors\Actions\CreateConnector;
use App\Domain\Connectors\Actions\DeleteConnector;
use App\Domain\Connectors\Actions\TestConnector;
use App\Domain\Connectors\Actions\UpdateConnector;
use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Http\Requests\CreateConnectorRequest;
use App\Domain\Connectors\Http\Requests\UpdateConnectorRequest;
use App\Domain\Connectors\Jobs\TestConnectorJob;
use App\Domain\Connectors\Models\Connector;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConnectorController extends Controller
{
    /**
     * Display the connectors list. The credentials never appear: the
     * props are built field by field and the model hides the encrypted
     * payload from every serialization.
     */
    public function index(): Response
    {
        $connectors = Connector::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Connector $connector): array => [
                'id' => $connector->id,
                'name' => $connector->name,
                'type' => $connector->type,
                'baseUrl' => $connector->base_url,
                'status' => $connector->status,
                'lastTestedAt' => $connector->last_tested_at?->toISOString(),
                'lastError' => $connector->last_error,
                'createdAt' => $connector->created_at->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Connectors/Index', ['connectors' => $connectors]);
    }

    /**
     * Display the connector creation form.
     */
    public function create(): Response
    {
        return Inertia::render('back/Connectors/Form');
    }

    /**
     * Store a new connector. The credentials are encrypted by the model
     * cast inside the action and never come back to the client.
     */
    public function store(CreateConnectorRequest $request, CreateConnector $action): RedirectResponse
    {
        $action->handle(...$request->validated());

        return redirect()->route('admin.connectors.index');
    }

    /**
     * Display the connector edition form. The credentials are never sent
     * back to the client, even masked: the form shows placeholders and
     * empty fields mean "keep the stored credentials".
     */
    public function edit(Connector $connector): Response
    {
        return Inertia::render('back/Connectors/Form', [
            'connector' => [
                'id' => $connector->id,
                'name' => $connector->name,
                'type' => $connector->type,
                'baseUrl' => $connector->base_url,
            ],
        ]);
    }

    /**
     * Update a connector. The credentials, when provided, replace their
     * stored counterpart and are re-encrypted; omitted, they stay
     * byte-identical.
     */
    public function update(UpdateConnectorRequest $request, Connector $connector, UpdateConnector $action): RedirectResponse
    {
        $action->handle($connector, $request->validated());

        return redirect()->route('admin.connectors.index');
    }

    /**
     * Run the connection test.
     *
     * The job owns the whole treatment. On the sync queue driver (local,
     * tests) the job runs inside this request, so the flash carries the
     * real outcome; on a real queue the outcome lands later and the
     * flash only acknowledges the dispatch with the neutral "unknown"
     * status until the next index visit.
     */
    public function test(Connector $connector): RedirectResponse
    {
        $job = new TestConnectorJob($connector);

        if (config('queue.default') === 'sync') {
            $result = $job->handle(app(TestConnector::class));
        } else {
            dispatch($job);

            $result = new ConnectorResult(false, Connector::STATUS_UNKNOWN, 0, null);
        }

        return redirect()
            ->route('admin.connectors.index')
            ->with('testResult', $result->toFlash());
    }

    /**
     * Delete a connector and its encrypted credentials with it.
     */
    public function destroy(Connector $connector, DeleteConnector $action): RedirectResponse
    {
        $action->handle($connector);

        return redirect()->route('admin.connectors.index');
    }
}
