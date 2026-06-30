<?php

use App\Enums\AffiliateProvider;
use Tests\TestCase;

uses(TestCase::class);
use App\Exceptions\ProviderException;
use App\Providers\Affiliate\WalmartProvider;
use Illuminate\Support\Facades\Http;

function walmartTestCredentials(): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $pem);

    return [
        'account_sid' => 'test-account-sid',
        'auth_token' => base64_encode($pem),
        'publisher_id' => '123456789',
    ];
}

it('maps walmart search response to ProductResult array', function (): void {
    Http::fake([
        'developer.api.walmart.com/*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/Walmart/search-response.json')), true),
        ),
    ]);

    $provider = new WalmartProvider;
    $results = $provider->search('headphones', walmartTestCredentials());

    expect($results)->toHaveCount(2)
        ->and($results[0]->provider)->toBe(AffiliateProvider::Walmart)
        ->and($results[0]->id)->toBe('12345678')
        ->and($results[0]->price)->toBe(279.99)
        ->and($results[0]->currency)->toBe('USD');
});

it('throws ProviderException on non-2xx response', function (): void {
    Http::fake(['developer.api.walmart.com/*' => Http::response([], 503)]);

    $provider = new WalmartProvider;

    expect(fn () => $provider->search('headphones', walmartTestCredentials()))
        ->toThrow(ProviderException::class);
});

it('throws ProviderException with clear message when private key is invalid', function (): void {
    Http::fake(['developer.api.walmart.com/*' => Http::response([], 200)]);

    $provider = new WalmartProvider;

    expect(fn () => $provider->search('headphones', [
        'account_sid' => 'test-account-sid',
        'auth_token' => base64_encode('not-a-valid-key'),
        'publisher_id' => '123456789',
    ]))->toThrow(ProviderException::class, 'Walmart signature generation failed');
});
