<?php

use App\Domain\Gateway\Models\ApiKey;
use App\Domain\Landing\Models\LandingPage;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;

beforeEach(function () {
    $this->seed(ModuleSeeder::class);
    Module::query()->where('slug', 'gateway')->update(['enabled' => true]);
    Module::query()->where('slug', 'landing')->update(['enabled' => true]);
});

/**
 * Create an active API key carrying both read abilities; the plaintext
 * secret is returned so tests can authenticate with it.
 */
function gatewayReaderSecret(): string
{
    $secret = gatewaySecret();
    gatewayKey($secret, [
        'abilities' => [ApiKey::ABILITY_SERVICES_READ, ApiKey::ABILITY_LANDINGS_READ],
    ]);

    return $secret;
}

test('the services endpoint lists only the activated modules', function () {
    $secret = gatewayReaderSecret();
    Module::query()->where('slug', 'pricing')->update(['enabled' => true]);

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.slug', 'landing')
        ->assertJsonPath('data.1.slug', 'pricing')
        ->assertJsonPath('data.2.slug', 'gateway');
});

test('the services endpoint enriches the modules with the registry metadata', function () {
    $secret = gatewayReaderSecret();

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'landing')
        ->assertJsonPath('data.0.label', 'Landing')
        ->assertJsonPath('data.0.description', 'Public landing page.')
        ->assertJsonPath('data.0.enabled', true)
        ->assertJsonPath('data.1.slug', 'gateway')
        ->assertJsonPath('data.1.label', 'Gateway')
        ->assertJsonPath('data.1.description', 'API gateway: keys, quotas and routing.')
        ->assertJsonPath('data.1.enabled', true);
});

test('a disabled module never appears in the services list', function () {
    $secret = gatewayReaderSecret();

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    Module::query()->where('slug', 'landing')->update(['enabled' => false]);

    $this->getJson('/api/v1/services', withBearer($secret))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'gateway')
        ->assertJsonMissing(['slug' => 'landing']);
});

test('the landings endpoint lists only the published pages', function () {
    $secret = gatewayReaderSecret();

    LandingPage::factory()->published()->create(['slug' => 'publiee', 'title' => 'Publiée']);
    LandingPage::factory()->create(['slug' => 'brouillon']);

    $this->getJson('/api/v1/landings', withBearer($secret))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'publiee')
        ->assertJsonPath('data.0.title', 'Publiée')
        ->assertJsonMissing(['slug' => 'brouillon']);
});

test('the landings endpoint exposes the hero and the persisted CTAs', function () {
    $secret = gatewayReaderSecret();

    LandingPage::factory()->published()->create([
        'slug' => 'avec-ctas',
        'title' => 'Page CTA',
        'hero_title' => 'Un beau héros',
        'hero_subtitle' => 'Un sous-titre clair',
        'ctas' => [
            ['label' => 'Demarrer', 'url' => 'https://services.test/register', 'style' => 'primary'],
            ['label' => 'Tarifs', 'url' => '/pricing', 'style' => 'secondary'],
        ],
    ]);

    $this->getJson('/api/v1/landings', withBearer($secret))
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'avec-ctas')
        ->assertJsonPath('data.0.title', 'Page CTA')
        ->assertJsonPath('data.0.heroTitle', 'Un beau héros')
        ->assertJsonPath('data.0.heroSubtitle', 'Un sous-titre clair')
        ->assertJsonCount(2, 'data.0.ctas')
        ->assertJsonPath('data.0.ctas.0.label', 'Demarrer')
        ->assertJsonPath('data.0.ctas.0.url', 'https://services.test/register')
        ->assertJsonPath('data.0.ctas.0.style', 'primary')
        ->assertJsonPath('data.0.ctas.1.label', 'Tarifs')
        ->assertJsonPath('data.0.ctas.1.style', 'secondary');
});

test('disabling the gateway module makes every API route 404', function () {
    $secret = gatewayReaderSecret();

    Module::query()->where('slug', 'gateway')->update(['enabled' => false]);

    $this->getJson('/api/v1/services', withBearer($secret))->assertNotFound();
    $this->getJson('/api/v1/landings', withBearer($secret))->assertNotFound();
});
