<?php

namespace App\Domain\Webhooks;

use App\Domain\Connectors\Events\ConnectorStatusChanged;
use App\Domain\Connectors\Models\Connector;
use App\Domain\Landing\Events\LandingPublished;
use App\Domain\Landing\Models\LandingPage;
use App\Domain\Pricing\Events\PricingTablePublished;
use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;

/**
 * The canonical webhook events of the platform.
 *
 * The list is extensible and doubles as documentation: add a case, then
 * teach "forDomainEvent" and "data" how to map the corresponding domain
 * event and which useful content travels in the payload. The case values
 * are the names validated at endpoint creation and shipped in every
 * payload envelope.
 */
enum WebhookEvent: string
{
    /**
     * A landing page was published.
     */
    case LandingPublished = 'landing.published';

    /**
     * A pricing table was published.
     */
    case PricingTablePublished = 'pricing.published';

    /**
     * A connector changed status after a connection test.
     */
    case ConnectorStatusChanged = 'connector.status_changed';

    /**
     * The canonical names, for validation and filter lists.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The webhook event matching a domain event, or null when the
     * platform carries no webhook for it (yet).
     */
    public static function forDomainEvent(object $event): ?self
    {
        return match (true) {
            $event instanceof LandingPublished => self::LandingPublished,
            $event instanceof PricingTablePublished => self::PricingTablePublished,
            $event instanceof ConnectorStatusChanged => self::ConnectorStatusChanged,
            default => null,
        };
    }

    /**
     * The useful content carried in the payload "data" section, shaped
     * per event type: exactly what a consumer needs to mirror the public
     * state, and nothing more.
     *
     * @return array<string, mixed>
     */
    public function data(object $event): array
    {
        return match (true) {
            $event instanceof LandingPublished => $this->landingData($event->landingPage),
            $event instanceof PricingTablePublished => $this->pricingData($event->pricingTable),
            $event instanceof ConnectorStatusChanged => $this->connectorData($event->connector),
            default => [],
        };
    }

    /**
     * @return array{slug: string, title: string, heroTitle: string, heroSubtitle: string, ctas: list<array{label: string, url: string, style: string}>}
     */
    private function landingData(LandingPage $landingPage): array
    {
        return [
            'slug' => $landingPage->slug,
            'title' => $landingPage->title,
            'heroTitle' => $landingPage->hero_title,
            'heroSubtitle' => $landingPage->hero_subtitle,
            'ctas' => $landingPage->ctas,
        ];
    }

    /**
     * Only active plans travel: the consumer mirrors what the public
     * pricing page actually renders.
     *
     * @return array{slug: string, title: string, subtitle: string, plans: list<array{slug: string, name: string, priceMonthly: int, priceYearly: int, popular: bool}>}
     */
    private function pricingData(PricingTable $pricingTable): array
    {
        return [
            'slug' => $pricingTable->slug,
            'title' => $pricingTable->title,
            'subtitle' => $pricingTable->subtitle,
            'plans' => array_values($pricingTable->activePlans()
                ->get()
                ->map(fn (PricingPlan $plan): array => [
                    'slug' => $plan->slug,
                    'name' => $plan->name,
                    'priceMonthly' => $plan->price_monthly,
                    'priceYearly' => $plan->price_yearly,
                    'popular' => $plan->popular,
                ])
                ->all()),
        ];
    }

    /**
     * @return array{name: string, type: string, status: string, baseUrl: string}
     */
    private function connectorData(Connector $connector): array
    {
        return [
            'name' => $connector->name,
            'type' => $connector->type,
            'status' => $connector->status,
            'baseUrl' => $connector->base_url,
        ];
    }
}
