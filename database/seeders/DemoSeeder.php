<?php

namespace Database\Seeders;

use App\Core\Modules\SetModuleState;
use App\Domain\Gateway\Models\ApiKey;
use App\Domain\Landing\Models\LandingPage;
use App\Domain\Pricing\Models\PricingPlan;
use App\Domain\Pricing\Models\PricingTable;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * The fixed plaintext secret of the demo API key, so the gateway API
     * can be exercised with curl right after seeding. Format follows
     * ApiKey::generateSecret() ("sk_" + 40 hex chars).
     */
    final public const DEMO_SECRET = 'sk_deadbeefdeadbeefdeadbeefdeadbeefdeadbeef';

    /**
     * The shared password of the demo accounts.
     */
    final public const DEMO_PASSWORD = 'demo1234';

    /**
     * Seed an operational demo: enabled modules, users for both roles,
     * published and draft content on every front-office module, and
     * gateway keys covering each lifecycle state. Safe to re-run: every
     * row is matched on a stable business key (email or slug).
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->seedModules();
        $this->seedLandingPages();
        $this->seedPricing();
        $this->seedApiKeys();

        $this->command?->info('Comptes : admin@services.test / editor@services.test — mot de passe "'.self::DEMO_PASSWORD.'".');
        $this->command?->info('Cle API de demonstration : "'.self::DEMO_SECRET.'".');
    }

    /**
     * Create one account per back-office role, both verified.
     */
    private function seedUsers(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@services.test'],
            [
                'name' => 'Admin Demo',
                'password' => self::DEMO_PASSWORD,
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'editor@services.test'],
            [
                'name' => 'Editor Demo',
                'password' => self::DEMO_PASSWORD,
                'role' => User::ROLE_EDITOR,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Enable every module that ships demo content.
     */
    private function seedModules(): void
    {
        $setState = app(SetModuleState::class);

        foreach (['landing', 'pricing', 'gateway'] as $slug) {
            $setState->handle($slug, true);
        }
    }

    /**
     * Create a published home page, a published secondary page and a
     * draft, so the back-office shows the three states at once.
     */
    private function seedLandingPages(): void
    {
        LandingPage::query()->updateOrCreate(
            ['slug' => 'accueil'],
            [
                'title' => 'Services',
                'hero_title' => 'Vos services métier, exposés et orchestrés en un seul endroit',
                'hero_subtitle' => 'La plateforme qui centralise vos API, vos pages d’atterrissage et vos tarifs. Publiez en quelques minutes, exposez en toute sécurité, mesurez en continu.',
                'ctas' => [
                    ['label' => 'Voir les tarifs', 'url' => '/pricing', 'style' => 'primary'],
                    ['label' => 'Se connecter', 'url' => '/login', 'style' => 'secondary'],
                ],
                'seo' => [
                    'title' => 'Services — la plateforme de vos services métier',
                    'description' => 'Centralisez vos API, pages d’atterrissage et tarifs dans une seule plateforme modulaire.',
                ],
                'is_home' => true,
                'published_at' => now(),
            ],
        );

        LandingPage::query()->updateOrCreate(
            ['slug' => 'a-propos'],
            [
                'title' => 'À propos',
                'hero_title' => 'Une plateforme modulaire, pensée pour grandir avec vous',
                'hero_subtitle' => 'Chaque module s’active indépendamment : landing pages, tarifs, passerelle API, connecteurs et webhooks arrivent progressivement.',
                'ctas' => [
                    ['label' => 'Retour à l’accueil', 'url' => '/', 'style' => 'secondary'],
                ],
                'seo' => [
                    'title' => 'À propos — Services',
                    'description' => 'Découvrez la philosophie modulaire de la plateforme Services.',
                ],
                'is_home' => false,
                'published_at' => now(),
            ],
        );

        LandingPage::query()->updateOrCreate(
            ['slug' => 'brouillon-test'],
            [
                'title' => 'Brouillon de test',
                'hero_title' => 'Page en préparation',
                'hero_subtitle' => 'Cette page n’est pas publiée : elle n’apparaît que dans le back-office et répond 404 en public.',
                'ctas' => [],
                'seo' => [
                    'title' => 'Brouillon — Services',
                    'description' => 'Page de test non publiée.',
                ],
                'is_home' => false,
                'published_at' => null,
            ],
        );
    }

    /**
     * Create three active plans and a published table presenting them,
     * plus a draft table to exercise the back-office states.
     */
    private function seedPricing(): void
    {
        $decouverte = PricingPlan::query()->updateOrCreate(
            ['slug' => 'decouverte'],
            [
                'name' => 'Découverte',
                'tagline' => 'Pour tester la plateforme sans engagement',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'EUR',
                'features' => [
                    '1 page d’atterrissage publiée',
                    '1 table de tarifs',
                    'Communauté et documentation',
                ],
                'popular' => false,
                'active' => true,
                'sort' => 1,
            ],
        );

        $pro = PricingPlan::query()->updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'tagline' => 'Pour les équipes qui livrent vite',
                'price_monthly' => 2990,
                'price_yearly' => 29900,
                'currency' => 'EUR',
                'features' => [
                    'Pages et tarifs illimités',
                    'Passerelle API incluse',
                    'Statistiques d’usage',
                    'Support prioritaire',
                ],
                'popular' => true,
                'active' => true,
                'sort' => 2,
            ],
        );

        $business = PricingPlan::query()->updateOrCreate(
            ['slug' => 'business'],
            [
                'name' => 'Business',
                'tagline' => 'Pour les organisations exigeantes',
                'price_monthly' => 9900,
                'price_yearly' => 99000,
                'currency' => 'EUR',
                'features' => [
                    'Tout le plan Pro',
                    'Limites de débit par clé',
                    'SLA et support dédié',
                    'Onboarding accompagné',
                ],
                'popular' => false,
                'active' => true,
                'sort' => 3,
            ],
        );

        $essentiel = PricingTable::query()->updateOrCreate(
            ['slug' => 'essentiel'],
            [
                'title' => 'Des tarifs simples et prévisibles',
                'subtitle' => 'Commencez gratuitement, évoluez quand vos besoins grandissent. Sans frais cachés, résiliable à tout moment.',
                'faq' => [
                    ['question' => 'Puis-je changer de plan à tout moment ?', 'answer' => 'Oui, la bascule est immédiate et la facturation est ajustée au prorata.'],
                    ['question' => 'Que comprend l’essai gratuit ?', 'answer' => 'Toutes les fonctionnalités du plan Découverte, sans carte bancaire.'],
                    ['question' => 'Comment est calculé le tarif annuel ?', 'answer' => 'Le tarif annuel correspond à deux mois offerts par rapport au paiement mensuel.'],
                    ['question' => 'Puis-je résilier quand je veux ?', 'answer' => 'Oui, sans préavis ni pénalité, depuis votre espace d’administration.'],
                ],
                'published_at' => now(),
            ],
        );

        $essentiel->plans()->sync([
            $decouverte->id => ['sort' => 0],
            $pro->id => ['sort' => 1],
            $business->id => ['sort' => 2],
        ]);

        PricingTable::query()->updateOrCreate(
            ['slug' => 'entreprise-test'],
            [
                'title' => 'Offre entreprise (brouillon)',
                'subtitle' => 'Table non publiée : visible uniquement dans le back-office.',
                'faq' => [],
                'published_at' => null,
            ],
        );
    }

    /**
     * Create gateway keys covering each lifecycle state: active (with a
     * known secret), nearing expiry, and revoked.
     */
    private function seedApiKeys(): void
    {
        if (ApiKey::query()->where('name', 'Intégration de démonstration')->doesntExist()) {
            ApiKey::factory()
                ->forSecret(self::DEMO_SECRET)
                ->createOne([
                    'name' => 'Intégration de démonstration',
                    'abilities' => [ApiKey::ABILITY_SERVICES_READ, ApiKey::ABILITY_LANDINGS_READ],
                    'rate_limit' => 120,
                    'last_used_at' => now()->subMinutes(2),
                ]);
        }

        if (ApiKey::query()->where('name', 'Pilote partenaire')->doesntExist()) {
            ApiKey::factory()->createOne([
                'name' => 'Pilote partenaire',
                'abilities' => [ApiKey::ABILITY_LANDINGS_READ],
                'expires_at' => now()->addDays(30),
            ]);
        }

        if (ApiKey::query()->where('name', 'Ancienne intégration')->doesntExist()) {
            ApiKey::factory()->revoked()->createOne([
                'name' => 'Ancienne intégration',
                'abilities' => [ApiKey::ABILITY_SERVICES_READ],
                'last_used_at' => now()->subDays(21),
            ]);
        }
    }
}
