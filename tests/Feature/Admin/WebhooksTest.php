<?php

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use App\Domain\Webhooks\WebhookEvent;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'webhooks')->update(['enabled' => true]);
    $this->withoutVite();
});

function webhookPayload(array $overrides = []): array
{
    return array_merge([
        'url' => 'https://consumer.test/hook',
        'events' => ['landing.published', 'pricing.published'],
        'active' => true,
    ], $overrides);
}

function webhooksUserWithRole(string $role): User
{
    return $role === User::ROLE_ADMIN
        ? User::factory()->admin()->create()
        : User::factory()->create();
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.webhooks.endpoints.index'))->assertRedirect(route('login'));
    $this->get(route('admin.webhooks.deliveries.index'))->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $admin = User::factory()->unverified()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.webhooks.endpoints.index'))
        ->assertRedirect(route('verification.notice'));
});

test('editors are forbidden from every webhook route', function (string $verb, string $route, array $payload) {
    $editor = webhooksUserWithRole(User::ROLE_EDITOR);

    $response = match ($verb) {
        'get' => $this->actingAs($editor)->get($route),
        'post' => $this->actingAs($editor)->post($route, $payload),
        'put' => $this->actingAs($editor)->put($route, $payload),
        'delete' => $this->actingAs($editor)->delete($route),
    };

    $response->assertForbidden();
})->with([
    'endpoints index' => fn (): array => ['get', route('admin.webhooks.endpoints.index'), []],
    'endpoints create' => fn (): array => ['get', route('admin.webhooks.endpoints.create'), []],
    'endpoints store' => fn (): array => ['post', route('admin.webhooks.endpoints.store'), webhookPayload()],
    'endpoints edit' => fn (): array => ['get', route('admin.webhooks.endpoints.edit', WebhookEndpoint::factory()->create()), []],
    'endpoints update' => fn (): array => ['put', route('admin.webhooks.endpoints.update', WebhookEndpoint::factory()->create()), webhookPayload()],
    'endpoints destroy' => fn (): array => ['delete', route('admin.webhooks.endpoints.destroy', WebhookEndpoint::factory()->create()), []],
    'endpoints regenerate' => fn (): array => ['post', route('admin.webhooks.endpoints.regenerate', WebhookEndpoint::factory()->create()), []],
    'deliveries index' => fn (): array => ['get', route('admin.webhooks.deliveries.index'), []],
    'deliveries show' => fn (): array => ['get', route('admin.webhooks.deliveries.show', WebhookDelivery::factory()->create()), []],
    'deliveries retry' => fn (): array => ['post', route('admin.webhooks.deliveries.retry', WebhookDelivery::factory()->failed()->create()), []],
]);

test('administrators see the endpoints index with the exact contract props', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://consumer.test/hook',
        'events' => ['landing.published'],
        'active' => true,
    ]);
    WebhookDelivery::factory()->for($endpoint, 'endpoint')->count(2)->create();

    $plainResponse = $this->actingAs($admin)
        ->get(route('admin.webhooks.endpoints.index'));

    $secret = $endpoint->refresh()->secret;

    $plainResponse->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Webhooks/Endpoints', false)
            ->has('endpoints', 1)
            ->where('endpoints.0.id', $endpoint->id)
            ->where('endpoints.0.url', 'https://consumer.test/hook')
            ->where('endpoints.0.events', ['landing.published'])
            ->where('endpoints.0.active', true)
            ->where('endpoints.0.deliveriesCount', 2)
            ->where('endpoints.0.createdAt', $endpoint->created_at->toISOString()));

    // The plaintext secret is nowhere in the rendered response.
    expect(str_contains((string) $plainResponse->getContent(), $secret))->toBeFalse();
});

test('administrators create an endpoint and the secret is flashed exactly once', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.webhooks.endpoints.store'), webhookPayload())
        ->assertRedirect(route('admin.webhooks.endpoints.index'));

    $endpoint = WebhookEndpoint::query()->sole();

    $captured = null;
    $this->actingAs($admin)
        ->get(route('admin.webhooks.endpoints.index'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$captured): void {
            $page->component('back/Webhooks/Endpoints', false)
                ->where('flash.secret', function (mixed $secret) use (&$captured): bool {
                    $captured = $secret;

                    return is_string($secret) && preg_match('/^[0-9a-f]{64}$/', $secret) === 1;
                });
        });

    expect($endpoint->url)->toBe('https://consumer.test/hook')
        ->and($endpoint->events)->toBe(['landing.published', 'pricing.published'])
        ->and($endpoint->active)->toBeTrue()
        ->and($endpoint->secret)->toBe($captured);

    // A second visit never carries the secret again.
    $this->actingAs($admin)
        ->get(route('admin.webhooks.endpoints.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('flash.secret', null));
});

test('invalid endpoint payloads are rejected', function (array $overrides, string $key) {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.webhooks.endpoints.store'), webhookPayload($overrides))
        ->assertSessionHasErrors($key);
})->with([
    'missing url' => [['url' => null], 'url'],
    'bad url' => [['url' => 'pas-une-url'], 'url'],
    'missing events' => [['events' => null], 'events'],
    'empty events' => [['events' => []], 'events'],
    'non array events' => [['events' => 'landing.published'], 'events'],
    'unknown event name' => [['events' => ['landing.published', 'landing.deleted']], 'events.1'],
    'missing active' => [['active' => null], 'active'],
    'non boolean active' => [['active' => 'oui'], 'active'],
]);

test('administrators update an endpoint without ever touching the secret', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $endpoint = WebhookEndpoint::factory()->inactive()->subscribedTo('landing.published')->create();

    $secretBefore = $endpoint->refresh()->secret;

    $this->actingAs($admin)
        ->put(route('admin.webhooks.endpoints.update', $endpoint), webhookPayload([
            'url' => 'https://moved.test/hook',
            'events' => ['pricing.published', 'connector.status_changed'],
            'active' => true,
        ]))
        ->assertRedirect(route('admin.webhooks.endpoints.index'));

    $fresh = $endpoint->fresh();

    expect($fresh->url)->toBe('https://moved.test/hook')
        ->and($fresh->events)->toBe(['pricing.published', 'connector.status_changed'])
        ->and($fresh->active)->toBeTrue()
        ->and($fresh->secret)->toBe($secretBefore);
});

test('a partial update keeps the omitted fields', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $endpoint = WebhookEndpoint::factory()->subscribedTo('landing.published')->create();

    $this->actingAs($admin)
        ->put(route('admin.webhooks.endpoints.update', $endpoint), ['url' => 'https://moved-again.test/hook'])
        ->assertRedirect(route('admin.webhooks.endpoints.index'));

    $fresh = $endpoint->fresh();

    expect($fresh->url)->toBe('https://moved-again.test/hook')
        ->and($fresh->events)->toBe(['landing.published'])
        ->and($fresh->active)->toBeTrue();
});

test('deleting an endpoint destroys its delivery journal with it', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $endpoint = WebhookEndpoint::factory()->create();
    WebhookDelivery::factory()->for($endpoint, 'endpoint')->count(3)->create();

    $this->actingAs($admin)
        ->delete(route('admin.webhooks.endpoints.destroy', $endpoint))
        ->assertRedirect(route('admin.webhooks.endpoints.index'));

    expect(WebhookEndpoint::query()->count())->toBe(0)
        ->and(WebhookDelivery::query()->count())->toBe(0);
});

test('regenerating the secret flashes the new one once and retires the old', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.webhooks.endpoints.store'), webhookPayload())
        ->assertRedirect(route('admin.webhooks.endpoints.index'));

    $endpoint = WebhookEndpoint::query()->sole();

    // The original secret is displayed exactly once.
    $oldSecret = null;
    $this->actingAs($admin)
        ->get(route('admin.webhooks.endpoints.index'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$oldSecret): void {
            $page->where('flash.secret', function (mixed $secret) use (&$oldSecret): bool {
                $oldSecret = $secret;

                return is_string($secret) && preg_match('/^[0-9a-f]{64}$/', $secret) === 1;
            });
        });

    $this->actingAs($admin)
        ->post(route('admin.webhooks.endpoints.regenerate', $endpoint))
        ->assertRedirect(route('admin.webhooks.endpoints.index'));

    // The new secret is displayed exactly once and differs from the old.
    $newSecret = null;
    $this->actingAs($admin)
        ->get(route('admin.webhooks.endpoints.index'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$newSecret): void {
            $page->where('flash.secret', function (mixed $secret) use (&$newSecret): bool {
                $newSecret = $secret;

                return is_string($secret) && preg_match('/^[0-9a-f]{64}$/', $secret) === 1;
            });
        });

    expect($newSecret)->not->toBe($oldSecret)
        ->and($endpoint->refresh()->secret)->toBe($newSecret);

    // A further visit never carries the secret again.
    $this->actingAs($admin)
        ->get(route('admin.webhooks.endpoints.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('flash.secret', null));
});

test('administrators browse the deliveries journal with its filter lists', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);

    $landing = WebhookEndpoint::factory()->create(['url' => 'https://aaa-landing.test/hook']);
    $pricing = WebhookEndpoint::factory()->create(['url' => 'https://bbb-pricing.test/hook']);

    $success = WebhookDelivery::factory()->successful()->for($landing, 'endpoint')->create();
    $failed = WebhookDelivery::factory()->failed()->forEvent(WebhookEvent::PricingTablePublished)->for($pricing, 'endpoint')->create();
    $pending = WebhookDelivery::factory()->for($pricing, 'endpoint')->create();

    $this->actingAs($admin)
        ->get(route('admin.webhooks.deliveries.index'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($pending, $landing, $pricing): void {
            $page->component('back/Webhooks/Deliveries', false)
                ->has('deliveries', 3)
                ->where('deliveries.0.id', $pending->id)
                ->where('deliveries.0.event', 'landing.published')
                ->where('deliveries.0.status', 'pending')
                ->where('deliveries.0.attempts', 0)
                ->where('deliveries.0.responseCode', null)
                ->where('deliveries.0.durationMs', null)
                ->where('deliveries.0.deliveredAt', null)
                ->where('deliveries.0.endpointUrl', $pricing->url)
                ->has('filters.status')
                ->has('filters.event')
                ->has('filters.endpointId')
                ->where('statuses', ['pending', 'success', 'failed'])
                ->where('events', ['landing.published', 'pricing.published', 'connector.status_changed'])
                ->where('endpoints.0.id', $landing->id)
                ->where('endpoints.0.url', $landing->url);
        });
});

test('the deliveries journal filters by status, event and endpoint', function (array $query, int $expected, ?string $status, ?string $event, ?int $endpointId) {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);

    $landing = WebhookEndpoint::factory()->create();
    $pricing = WebhookEndpoint::factory()->create();

    WebhookDelivery::factory()->successful()->for($landing, 'endpoint')->create();
    WebhookDelivery::factory()->failed()->forEvent(WebhookEvent::PricingTablePublished)->for($pricing, 'endpoint')->create();
    WebhookDelivery::factory()->for($pricing, 'endpoint')->create();

    $this->actingAs($admin)
        ->get(route('admin.webhooks.deliveries.index', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deliveries', $expected)
            ->where('filters.status', $status)
            ->where('filters.event', $event)
            ->where('filters.endpointId', $endpointId));
})->with([
    'no filter' => [[], 3, null, null, null],
    'by status' => [['status' => 'failed'], 1, 'failed', null, null],
    'by event' => [['event' => 'pricing.published'], 1, null, 'pricing.published', null],
    'unknown status is ignored' => [['status' => 'invented'], 3, null, null, null],
]);

test('the endpoint filter narrows the journal to one endpoint', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);

    $landing = WebhookEndpoint::factory()->create();
    $pricing = WebhookEndpoint::factory()->create();

    WebhookDelivery::factory()->successful()->for($landing, 'endpoint')->create();
    WebhookDelivery::factory()->failed()->for($pricing, 'endpoint')->create();
    WebhookDelivery::factory()->for($pricing, 'endpoint')->create();

    $this->actingAs($admin)
        ->get(route('admin.webhooks.deliveries.index', ['endpoint' => $pricing->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('deliveries', 2)
            ->where('filters.endpointId', $pricing->id));
});

test('administrators open a delivery and see its journal line', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $endpoint = webhookEndpoint(webhookSecret(), ['url' => 'https://consumer.test/hook']);
    $delivery = WebhookDelivery::factory()->failed()->for($endpoint, 'endpoint')->create([
        'payload' => [
            'event' => 'landing.published',
            'occurred_at' => '2026-10-28T08:00:00.000000Z',
            'data' => ['slug' => 'home'],
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.webhooks.deliveries.show', $delivery))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back/Webhooks/Delivery', false)
            ->where('delivery.id', $delivery->id)
            ->where('delivery.event', 'landing.published')
            ->where('delivery.status', 'failed')
            ->where('delivery.attempts', 5)
            ->where('delivery.payload', [
                'event' => 'landing.published',
                'occurred_at' => '2026-10-28T08:00:00.000000Z',
                'data' => ['slug' => 'home'],
            ])
            ->where('delivery.responseCode', 500)
            ->where('delivery.error', 'HTTP 500 response.')
            ->where('delivery.durationMs', 120)
            ->where('delivery.deliveredAt', null)
            ->where('delivery.endpointUrl', 'https://consumer.test/hook'));
});

test('the retry action relaunches a failed delivery through the route', function () {
    Queue::fake();

    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $delivery = WebhookDelivery::factory()->failed()->create();

    $this->actingAs($admin)
        ->post(route('admin.webhooks.deliveries.retry', $delivery))
        ->assertRedirect(route('admin.webhooks.deliveries.show', $delivery));

    $fresh = $delivery->fresh();

    expect($fresh->status)->toBe(WebhookDelivery::STATUS_PENDING)
        ->and($fresh->attempts)->toBe(0);

    Queue::assertPushed(DeliverWebhookJob::class, fn (DeliverWebhookJob $job): bool => $job->deliveryId === $delivery->id);
});

test('the retry button runs the delivery inline with the sync queue driver', function () {
    Http::fake(['*' => Http::response(null, 200)]);

    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $delivery = WebhookDelivery::factory()->failed()->create();

    $this->actingAs($admin)
        ->post(route('admin.webhooks.deliveries.retry', $delivery))
        ->assertRedirect(route('admin.webhooks.deliveries.show', $delivery));

    $fresh = $delivery->fresh();

    expect($fresh->status)->toBe(WebhookDelivery::STATUS_SUCCESS)
        ->and($fresh->attempts)->toBe(1)
        ->and($fresh->response_code)->toBe(200)
        ->and($fresh->delivered_at)->not->toBeNull();
});

test('retrying a successful delivery changes nothing', function () {
    Queue::fake();

    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $delivery = WebhookDelivery::factory()->successful()->create();

    $this->actingAs($admin)
        ->post(route('admin.webhooks.deliveries.retry', $delivery))
        ->assertRedirect(route('admin.webhooks.deliveries.show', $delivery));

    expect($delivery->fresh()->status)->toBe(WebhookDelivery::STATUS_SUCCESS)
        ->and($delivery->fresh()->attempts)->toBe(1);

    Queue::assertNothingPushed();
});

test('disabling the module makes the admin routes 404', function () {
    $admin = webhooksUserWithRole(User::ROLE_ADMIN);
    $endpoint = WebhookEndpoint::factory()->create();
    $delivery = WebhookDelivery::factory()->failed()->for($endpoint, 'endpoint')->create();

    Module::query()->where('slug', 'webhooks')->update(['enabled' => false]);

    $this->actingAs($admin)->get(route('admin.webhooks.endpoints.index'))->assertNotFound();
    $this->actingAs($admin)->post(route('admin.webhooks.endpoints.store'), webhookPayload())->assertNotFound();
    $this->actingAs($admin)->post(route('admin.webhooks.endpoints.regenerate', $endpoint))->assertNotFound();
    $this->actingAs($admin)->get(route('admin.webhooks.deliveries.index'))->assertNotFound();
    $this->actingAs($admin)->post(route('admin.webhooks.deliveries.retry', $delivery))->assertNotFound();
});
