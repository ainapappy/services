<?php

namespace App\Domain\Webhooks\Http\Controllers\Admin;

use App\Domain\Webhooks\Actions\RetryDelivery;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use App\Domain\Webhooks\WebhookEvent;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryController extends Controller
{
    /**
     * Display the deliveries journal, filterable by status, canonical
     * event and endpoint. The filter values travel in the query string
     * (status, event, endpoint); unknown values are ignored rather than
     * rejected, so a stale filter never breaks the page, and only the
     * applied values are echoed back to the UI.
     */
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $event = $request->string('event')->toString();
        $endpointId = $request->integer('endpoint');

        $appliedStatus = in_array($status, WebhookDelivery::STATUSES, true) ? $status : null;
        $appliedEvent = in_array($event, WebhookEvent::values(), true) ? $event : null;
        $appliedEndpointId = $endpointId > 0 ? $endpointId : null;

        $deliveries = WebhookDelivery::query()
            ->with('endpoint:id,url')
            ->when($appliedStatus !== null, fn (Builder $query): Builder => $query->where('status', $appliedStatus))
            ->when($appliedEvent !== null, fn (Builder $query): Builder => $query->where('event', $appliedEvent))
            ->when($appliedEndpointId !== null, fn (Builder $query): Builder => $query->where('webhook_endpoint_id', $appliedEndpointId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WebhookDelivery $delivery): array => [
                'id' => $delivery->id,
                'event' => $delivery->event,
                'status' => $delivery->status,
                'attempts' => $delivery->attempts,
                'responseCode' => $delivery->response_code,
                'durationMs' => $delivery->duration_ms,
                'createdAt' => $delivery->created_at->toISOString(),
                'deliveredAt' => $delivery->delivered_at?->toISOString(),
                'endpointUrl' => $delivery->endpoint->url,
            ])
            ->values()
            ->all();

        $endpoints = WebhookEndpoint::query()
            ->orderBy('url')
            ->get()
            ->map(fn (WebhookEndpoint $endpoint): array => [
                'id' => $endpoint->id,
                'url' => $endpoint->url,
            ])
            ->values()
            ->all();

        return Inertia::render('back/Webhooks/Deliveries', [
            'deliveries' => $deliveries,
            'filters' => [
                'status' => $appliedStatus,
                'event' => $appliedEvent,
                'endpointId' => $appliedEndpointId,
            ],
            'statuses' => WebhookDelivery::STATUSES,
            'events' => WebhookEvent::values(),
            'endpoints' => $endpoints,
        ]);
    }

    /**
     * Display one delivery: the signed payload envelope, the attempts and
     * the outcome of the last attempt.
     */
    public function show(WebhookDelivery $webhookDelivery): Response
    {
        return Inertia::render('back/Webhooks/Delivery', [
            'delivery' => [
                'id' => $webhookDelivery->id,
                'event' => $webhookDelivery->event,
                'payload' => $webhookDelivery->payload,
                'status' => $webhookDelivery->status,
                'attempts' => $webhookDelivery->attempts,
                'responseCode' => $webhookDelivery->response_code,
                'error' => $webhookDelivery->error,
                'durationMs' => $webhookDelivery->duration_ms,
                'createdAt' => $webhookDelivery->created_at->toISOString(),
                'deliveredAt' => $webhookDelivery->delivered_at?->toISOString(),
                'endpointUrl' => $webhookDelivery->endpoint->url,
            ],
        ]);
    }

    /**
     * Relaunch a failed delivery: new campaign, counter reset, immediate
     * dispatch.
     */
    public function retry(WebhookDelivery $webhookDelivery, RetryDelivery $action): RedirectResponse
    {
        $action->handle($webhookDelivery);

        return redirect()->route('admin.webhooks.deliveries.show', $webhookDelivery);
    }
}
