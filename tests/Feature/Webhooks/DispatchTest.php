<?php

use App\Domain\Connectors\Actions\TestConnector;
use App\Domain\Connectors\Models\Connector;
use App\Domain\Landing\Actions\PublishLandingPage;
use App\Domain\Landing\Models\LandingPage;
use App\Domain\Pricing\Actions\PublishTable;
use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use App\Domain\Webhooks\WebhookEvent;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    $this->withoutVite();
});

test('a domain event creates one pending delivery per subscribed endpoint', function () {
    Queue::fake();

    $first = WebhookEndpoint::factory()->subscribedTo(WebhookEvent::LandingPublished->value)->create();
    $second = WebhookEndpoint::factory()->subscribedTo(
        WebhookEvent::LandingPublished->value,
        WebhookEvent::ConnectorStatusChanged->value,
    )->create();

    $landingPage = LandingPage::factory()->create();
    (new PublishLandingPage)->handle($landingPage);

    expect(WebhookDelivery::query()->count())->toBe(2)
        ->and(WebhookDelivery::query()->where('webhook_endpoint_id', $first->id)->count())->toBe(1)
        ->and(WebhookDelivery::query()->where('webhook_endpoint_id', $second->id)->count())->toBe(1);

    $delivery = WebhookDelivery::query()->where('webhook_endpoint_id', $first->id)->sole();

    expect($delivery->status)->toBe(WebhookDelivery::STATUS_PENDING)
        ->and($delivery->attempts)->toBe(0)
        ->and($delivery->event)->toBe('landing.published')
        ->and($delivery->payload['event'])->toBe('landing.published')
        ->and($delivery->payload['occurred_at'])->toBeString()
        ->and($delivery->payload['data']['slug'])->toBe($landingPage->slug)
        ->and($delivery->payload['data']['title'])->toBe($landingPage->title)
        ->and($delivery->payload['data']['heroTitle'])->toBe($landingPage->hero_title)
        ->and($delivery->payload['data']['heroSubtitle'])->toBe($landingPage->hero_subtitle)
        ->and($delivery->payload['data']['ctas'])->toBe($landingPage->ctas);

    Queue::assertPushed(DeliverWebhookJob::class, 2);
    Queue::assertPushed(DeliverWebhookJob::class, fn (DeliverWebhookJob $job): bool => $job->deliveryId === $first->id);
});

test('an inactive endpoint receives nothing', function () {
    Queue::fake();

    WebhookEndpoint::factory()->inactive()->subscribedTo(WebhookEvent::LandingPublished->value)->create();

    (new PublishLandingPage)->handle(LandingPage::factory()->create());

    expect(WebhookDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

test('an endpoint not subscribed to the event receives nothing', function () {
    Queue::fake();

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::PricingTablePublished->value)->create();

    (new PublishLandingPage)->handle(LandingPage::factory()->create());

    expect(WebhookDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

test('unpublishing a landing page never fires the event', function () {
    Queue::fake();

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::LandingPublished->value)->create();
    $landingPage = LandingPage::factory()->create();

    (new PublishLandingPage)->handle($landingPage);
    (new PublishLandingPage)->handle($landingPage);

    expect(WebhookDelivery::query()->count())->toBe(1);
});

test('publishing a pricing table pushes its active plans to subscribed endpoints', function () {
    Queue::fake();

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::PricingTablePublished->value)->create();

    $popular = PricingPlan::factory()->active()->popular()->create([
        'slug' => 'pro',
        'name' => 'Pro',
        'price_monthly' => 2900,
        'price_yearly' => 29000,
    ]);
    $basic = PricingPlan::factory()->active()->create(['slug' => 'basic']);
    $inactive = PricingPlan::factory()->create(['slug' => 'ghost']);

    $pricingTable = PricingTable::factory()->create();
    $pricingTable->plans()->attach([
        $basic->id => ['sort' => 0],
        $popular->id => ['sort' => 1],
        $inactive->id => ['sort' => 2],
    ]);

    (new PublishTable)->handle($pricingTable);

    $delivery = WebhookDelivery::query()->sole();

    expect($delivery->event)->toBe('pricing.published')
        ->and($delivery->payload['data']['slug'])->toBe($pricingTable->slug)
        ->and($delivery->payload['data']['title'])->toBe($pricingTable->title)
        ->and($delivery->payload['data']['subtitle'])->toBe($pricingTable->subtitle)
        ->and($delivery->payload['data']['plans'])->toBe([
            ['slug' => 'basic', 'name' => $basic->name, 'priceMonthly' => 2900, 'priceYearly' => 29000, 'popular' => false],
            ['slug' => 'pro', 'name' => 'Pro', 'priceMonthly' => 2900, 'priceYearly' => 29000, 'popular' => true],
        ]);

    Queue::assertPushed(DeliverWebhookJob::class, 1);
});

test('a connector status change pushes the new status to subscribed endpoints', function () {
    Queue::fake();
    Http::fake(['*' => Http::response(null, 200)]);

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::ConnectorStatusChanged->value)->create();
    $connector = Connector::factory()->create();

    expect($connector->status)->toBe(Connector::STATUS_UNKNOWN);

    app(TestConnector::class)->handle($connector);

    $delivery = WebhookDelivery::query()->sole();

    expect($delivery->event)->toBe('connector.status_changed')
        ->and($delivery->payload['data']['name'])->toBe($connector->name)
        ->and($delivery->payload['data']['type'])->toBe($connector->type)
        ->and($delivery->payload['data']['status'])->toBe(Connector::STATUS_CONNECTED)
        ->and($delivery->payload['data']['baseUrl'])->toBe($connector->base_url);

    Queue::assertPushed(DeliverWebhookJob::class, 1);
});

test('retesting an unchanged connector pushes nothing', function () {
    Queue::fake();
    Http::fake(['*' => Http::response(null, 200)]);

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::ConnectorStatusChanged->value)->create();
    $connector = Connector::factory()->connected()->create();

    app(TestConnector::class)->handle($connector);

    expect(WebhookDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

test('a failed connection test changing the status pushes the new status', function () {
    Queue::fake();
    Http::fake(['*' => Http::response('boom', 500)]);

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::ConnectorStatusChanged->value)->create();
    $connector = Connector::factory()->create();

    app(TestConnector::class)->handle($connector);

    $delivery = WebhookDelivery::query()->sole();

    expect($connector->fresh()->status)->toBe(Connector::STATUS_ERROR)
        ->and($delivery->payload['data']['status'])->toBe(Connector::STATUS_ERROR);
});

test('a full delivery campaign runs inline with the sync queue driver', function () {
    Http::fake(['*' => Http::response(null, 200)]);

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::LandingPublished->value)->create();
    $landingPage = LandingPage::factory()->create();

    (new PublishLandingPage)->handle($landingPage);

    $delivery = WebhookDelivery::query()->sole();

    expect($delivery->status)->toBe(WebhookDelivery::STATUS_SUCCESS)
        ->and($delivery->response_code)->toBe(200)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($delivery->delivered_at)->not->toBeNull()
        ->and($delivery->error)->toBeNull();
});

test('a failing endpoint does not break the publishing action under the sync driver', function () {
    Http::fake(['*' => Http::response('boom', 500)]);

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::LandingPublished->value)->create();
    $landingPage = LandingPage::factory()->create();

    // The inline delivery attempt fails and is swallowed by the dispatcher:
    // the business action must complete regardless.
    (new PublishLandingPage)->handle($landingPage);

    $delivery = WebhookDelivery::query()->sole();

    expect($landingPage->fresh()->published_at)->not->toBeNull()
        ->and($delivery->status)->toBe(WebhookDelivery::STATUS_PENDING)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->response_code)->toBe(500);
});

test('unpublishing a pricing table dispatches nothing', function () {
    Queue::fake();

    WebhookEndpoint::factory()->subscribedTo(WebhookEvent::PricingTablePublished->value)->create();
    $pricingTable = PricingTable::factory()->create();

    (new PublishTable)->handle($pricingTable);

    expect(WebhookDelivery::query()->count())->toBe(1);

    // Second call toggles back to draft: no new delivery.
    (new PublishTable)->handle($pricingTable);

    expect(WebhookDelivery::query()->count())->toBe(1)
        ->and($pricingTable->fresh()->published_at)->toBeNull();

    Queue::assertPushed(DeliverWebhookJob::class, 1);
});
