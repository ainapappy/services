<?php

use App\Domain\Connectors\ConnectorsServiceProvider;
use App\Domain\Gateway\GatewayServiceProvider;
use App\Domain\Landing\LandingServiceProvider;
use App\Domain\Monitoring\MonitoringServiceProvider;
use App\Domain\Pricing\PricingServiceProvider;
use App\Domain\Webhooks\WebhooksServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MailServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MailServiceProvider::class,
    ConnectorsServiceProvider::class,
    GatewayServiceProvider::class,
    LandingServiceProvider::class,
    MonitoringServiceProvider::class,
    PricingServiceProvider::class,
    WebhooksServiceProvider::class,
];
