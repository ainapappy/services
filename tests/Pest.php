<?php

use App\Domain\Gateway\Models\ApiKey;
use App\Domain\Webhooks\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeEmojiFree', function () {
    preg_match(
        '/[\x{1F000}-\x{1FFFF}\x{2B00}-\x{2BFF}\x{2600}-\x{27BF}\x{FE0F}\x{2049}\x{203C}\x{2190}-\x{21FF}]/u',
        (string) $this->value,
        $matches,
    );

    expect($matches)->toBeEmpty();

    return $this;
});

/*
|--------------------------------------------------------------------------
| Global setup
|--------------------------------------------------------------------------
|
| Inertia page renders (the 404 pages included) resolve their page
| entry against the Vite manifest: the suite must stay independent of
| any frontend build, so Vite is faked for every test.
|
*/

beforeEach(function () {
    $this->withoutVite();
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Gateway helpers
|--------------------------------------------------------------------------
*/

/**
 * Generate a fresh plaintext API key secret ("sk_" + 40 hex chars).
 */
function gatewaySecret(): string
{
    return ApiKey::generateSecret();
}

/**
 * Create an API key whose plaintext secret is known to the test.
 */
function gatewayKey(string $secret, array $attributes = []): ApiKey
{
    return ApiKey::factory()->forSecret($secret)->create($attributes);
}

/**
 * The headers sending a secret through the "Authorization: Bearer" scheme.
 */
function withBearer(string $secret): array
{
    return ['Authorization' => 'Bearer '.$secret];
}

/*
|--------------------------------------------------------------------------
| Webhooks helpers
|--------------------------------------------------------------------------
*/

/**
 * Generate a fresh plaintext webhook secret (64 hex chars).
 */
function webhookSecret(): string
{
    return WebhookEndpoint::generateSecret();
}

/**
 * Create a webhook endpoint whose plaintext secret is known to the test.
 */
function webhookEndpoint(string $secret, array $attributes = []): WebhookEndpoint
{
    return WebhookEndpoint::factory()->create(array_merge($attributes, ['secret' => $secret]));
}
