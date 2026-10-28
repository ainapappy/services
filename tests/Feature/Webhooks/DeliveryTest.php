<?php

use App\Domain\Webhooks\Actions\RetryDelivery;
use App\Domain\Webhooks\Events\WebhookDeliveryFailed;
use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\WebhookEvent;
use Database\Seeders\ModuleSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    $this->withoutVite();
});

/**
 * Run one job execution, expecting a retryable failure: the outcome is
 * already persisted on the row, the throw is what schedules the next
 * attempt on a real queue.
 */
function expectRetryableFailure(DeliverWebhookJob $job): void
{
    expect(fn () => $job->handle())->toThrow(RuntimeException::class);
}

test('the job is configured for a five-attempt campaign with the documented backoff', function () {
    $job = new DeliverWebhookJob(1);

    expect($job->tries)->toBe(5)
        ->and($job->backoff)->toBe([30, 300, 1800]);
});

test('the job signs the stored envelope and posts it to the endpoint', function () {
    $secret = webhookSecret();
    $endpoint = webhookEndpoint($secret, ['url' => 'https://endpoint.test/hook']);
    $delivery = WebhookDelivery::factory()
        ->for($endpoint, 'endpoint')
        ->create(['payload' => [
            'event' => WebhookEvent::LandingPublished->value,
            'occurred_at' => '2026-10-28T08:00:00.000000Z',
            'data' => ['slug' => 'home', 'title' => 'Home'],
        ]]);

    $capturedTimestamp = null;
    Http::fake(function (Request $request) use (&$capturedTimestamp) {
        $capturedTimestamp = $request->header('X-Timestamp')[0] ?? null;

        return Http::response(null, 200);
    });

    (new DeliverWebhookJob($delivery->id))->handle();

    Http::assertSent(function (Request $request) use ($secret, $delivery): bool {
        $body = $request->body();

        return $request->url() === 'https://endpoint.test/hook'
            && $request->header('X-Signature')[0] === hash_hmac('sha256', $body, $secret)
            && $request->header('X-Event')[0] === WebhookEvent::LandingPublished->value
            && $request->hasHeader('X-Timestamp')
            && $request->header('Content-Type')[0] === 'application/json'
            && json_decode($body, true) === $delivery->payload;
    });

    expect($capturedTimestamp)->toMatch('/^\d{10}$/');
});

test('a 200 answer journals a success with code and duration', function () {
    $endpoint = webhookEndpoint(webhookSecret());
    $delivery = WebhookDelivery::factory()->for($endpoint, 'endpoint')->create();

    Http::fake(['*' => Http::response(null, 200)]);

    (new DeliverWebhookJob($delivery->id))->handle();

    $fresh = $delivery->fresh();

    expect($fresh->status)->toBe(WebhookDelivery::STATUS_SUCCESS)
        ->and($fresh->response_code)->toBe(200)
        ->and($fresh->attempts)->toBe(1)
        ->and($fresh->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($fresh->delivered_at)->not->toBeNull()
        ->and($fresh->error)->toBeNull();
});

test('a 500 answer schedules retries until the campaign is exhausted', function () {
    Event::fake([WebhookDeliveryFailed::class]);

    $endpoint = webhookEndpoint(webhookSecret());
    $delivery = WebhookDelivery::factory()->for($endpoint, 'endpoint')->create();
    $job = new DeliverWebhookJob($delivery->id);

    // Four retryable failures: each one persists its real attempt before
    // throwing for the queue to schedule the next one.
    Http::fake(['*' => Http::response(null, 500)]);
    expectRetryableFailure($job);
    expectRetryableFailure($job);
    expectRetryableFailure($job);
    expectRetryableFailure($job);

    expect($delivery->fresh()->attempts)->toBe(4)
        ->and($delivery->fresh()->status)->toBe(WebhookDelivery::STATUS_PENDING);

    // The fifth failure exhausts the campaign: the delivery is declared
    // failed and monitoring is notified.
    $job->handle();

    $fresh = $delivery->fresh();

    expect($fresh->attempts)->toBe(5)
        ->and($fresh->status)->toBe(WebhookDelivery::STATUS_FAILED)
        ->and($fresh->response_code)->toBe(500)
        ->and($fresh->error)->toBe('HTTP 500 response.')
        ->and($fresh->delivered_at)->toBeNull();

    Event::assertDispatched(WebhookDeliveryFailed::class, 1);
    Event::assertDispatched(WebhookDeliveryFailed::class, fn (WebhookDeliveryFailed $event): bool => $event->delivery->is($delivery));
});

test('an unreachable host journals a safe error without a response code', function () {
    $endpoint = webhookEndpoint(webhookSecret());
    $delivery = WebhookDelivery::factory()->for($endpoint, 'endpoint')->create();

    Http::fake(fn () => throw new ConnectionException(
        'cURL error 6: Could not resolve host: endpoint.test'
    ));

    expectRetryableFailure(new DeliverWebhookJob($delivery->id));

    $fresh = $delivery->fresh();

    expect($fresh->attempts)->toBe(1)
        ->and($fresh->response_code)->toBeNull()
        ->and($fresh->error)->toBe('Host unreachable.')
        ->and($fresh->duration_ms)->toBeGreaterThanOrEqual(0);
});

test('a retry starts a fresh campaign from zero', function () {
    Queue::fake();

    $delivery = WebhookDelivery::factory()->failed()->create();

    $retried = (new RetryDelivery)->handle($delivery);

    expect($retried->status)->toBe(WebhookDelivery::STATUS_PENDING)
        ->and($retried->attempts)->toBe(0)
        // The last outcome stays on the row until the new attempt
        // overwrites it.
        ->and($retried->error)->toBe('HTTP 500 response.')
        ->and($retried->response_code)->toBe(500);

    Queue::assertPushed(DeliverWebhookJob::class, fn (DeliverWebhookJob $job): bool => $job->deliveryId === $delivery->id);

    // The new campaign restarts from zero and succeeds.
    Http::fake(['*' => Http::response(null, 200)]);
    (new DeliverWebhookJob($delivery->id))->handle();

    $fresh = $delivery->fresh();

    expect($fresh->status)->toBe(WebhookDelivery::STATUS_SUCCESS)
        ->and($fresh->attempts)->toBe(1)
        ->and($fresh->response_code)->toBe(200)
        ->and($fresh->delivered_at)->not->toBeNull()
        ->and($fresh->error)->toBeNull();
});

test('a delivery that is not failed is never retried', function () {
    Queue::fake();

    $pending = WebhookDelivery::factory()->create();
    $successful = WebhookDelivery::factory()->successful()->create();

    (new RetryDelivery)->handle($pending);
    (new RetryDelivery)->handle($successful);

    expect($pending->fresh()->status)->toBe(WebhookDelivery::STATUS_PENDING)
        ->and($pending->fresh()->attempts)->toBe(0)
        ->and($successful->fresh()->status)->toBe(WebhookDelivery::STATUS_SUCCESS)
        ->and($successful->fresh()->attempts)->toBe(1);

    Queue::assertNothingPushed();
});

test('the secret never leaks into the job serialization or the model output', function () {
    $secret = webhookSecret();
    $endpoint = webhookEndpoint($secret, ['url' => 'https://endpoint.test/hook']);
    $delivery = WebhookDelivery::factory()->for($endpoint, 'endpoint')->create();

    $job = new DeliverWebhookJob($delivery->id);
    $serialized = serialize($job);

    expect(str_contains($serialized, $secret))->toBeFalse()
        ->and(str_contains((string) $delivery->fresh()->toJson(), $secret))->toBeFalse()
        ->and($delivery->fresh()->toArray())->not->toHaveKey('secret');
});
