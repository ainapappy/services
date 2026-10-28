<?php

namespace App\Domain\Webhooks\Http\Controllers\Admin;

use App\Domain\Webhooks\Actions\CreateWebhookEndpoint;
use App\Domain\Webhooks\Actions\DeleteWebhookEndpoint;
use App\Domain\Webhooks\Actions\RegenerateWebhookSecret;
use App\Domain\Webhooks\Actions\UpdateWebhookEndpoint;
use App\Domain\Webhooks\Http\Requests\CreateWebhookEndpointRequest;
use App\Domain\Webhooks\Http\Requests\UpdateWebhookEndpointRequest;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EndpointController extends Controller
{
    /**
     * Display the webhook endpoints list. The secret never appears: the
     * props are built field by field and the model hides it from every
     * serialization.
     */
    public function index(): Response
    {
        $endpoints = WebhookEndpoint::query()
            ->withCount('deliveries')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (WebhookEndpoint $endpoint): array => [
                'id' => $endpoint->id,
                'url' => $endpoint->url,
                'events' => $endpoint->events,
                'active' => $endpoint->active,
                'deliveriesCount' => $endpoint->deliveries_count,
                'createdAt' => $endpoint->created_at->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Webhooks/Endpoints', ['endpoints' => $endpoints]);
    }

    /**
     * Display the endpoint creation form.
     */
    public function create(): Response
    {
        return Inertia::render('back/Webhooks/EndpointForm');
    }

    /**
     * Store a new endpoint. The secret is generated inside the action,
     * stored encrypted and flashed exactly once; it never comes back to
     * the client afterwards.
     */
    public function store(CreateWebhookEndpointRequest $request, CreateWebhookEndpoint $action): RedirectResponse
    {
        $created = $action->handle(...$request->validated());

        return redirect()
            ->route('admin.webhooks.endpoints.index')
            ->with('secret', $created->secret);
    }

    /**
     * Display the endpoint edition form. The secret is never sent back
     * to the client, even masked: only url, events and active travel.
     */
    public function edit(WebhookEndpoint $webhookEndpoint): Response
    {
        return Inertia::render('back/Webhooks/EndpointForm', [
            'endpoint' => [
                'id' => $webhookEndpoint->id,
                'url' => $webhookEndpoint->url,
                'events' => $webhookEndpoint->events,
                'active' => $webhookEndpoint->active,
            ],
        ]);
    }

    /**
     * Update an endpoint. The secret is untouched: it only changes
     * through the regenerate endpoint.
     */
    public function update(UpdateWebhookEndpointRequest $request, WebhookEndpoint $webhookEndpoint, UpdateWebhookEndpoint $action): RedirectResponse
    {
        $action->handle($webhookEndpoint, $request->validated());

        return redirect()->route('admin.webhooks.endpoints.index');
    }

    /**
     * Regenerate the secret of an endpoint: the new one is flashed
     * exactly once, the old one stops working immediately.
     */
    public function regenerate(WebhookEndpoint $webhookEndpoint, RegenerateWebhookSecret $action): RedirectResponse
    {
        $secret = $action->handle($webhookEndpoint);

        return redirect()
            ->route('admin.webhooks.endpoints.index')
            ->with('secret', $secret);
    }

    /**
     * Delete an endpoint and its delivery journal with it.
     */
    public function destroy(WebhookEndpoint $webhookEndpoint, DeleteWebhookEndpoint $action): RedirectResponse
    {
        $action->handle($webhookEndpoint);

        return redirect()->route('admin.webhooks.endpoints.index');
    }
}
