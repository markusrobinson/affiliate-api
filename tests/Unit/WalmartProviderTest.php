<?php

use App\Enums\AffiliateProvider;
use Tests\TestCase;

uses(TestCase::class);
use App\Exceptions\ProviderException;
use App\Providers\Affiliate\WalmartProvider;
use Illuminate\Support\Facades\Http;

it('maps walmart search response to ProductResult array', function (): void {
    Http::fake([
        'affiliate.api.walmart.com/*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/Walmart/search-response.json')), true),
        ),
    ]);

    $provider = new WalmartProvider;
    $results = $provider->search('headphones', [
        'account_sid' => 'test-account-sid',
        'auth_token' => 'test-auth-token',
    ]);

    expect($results)->toHaveCount(2)
        ->and($results[0]->provider)->toBe(AffiliateProvider::Walmart)
        ->and($results[0]->id)->toBe('12345678')
        ->and($results[0]->price)->toBe(279.99)
        ->and($results[0]->currency)->toBe('USD');
});

it('throws ProviderException on non-2xx response', function (): void {
    Http::fake(['affiliate.api.walmart.com/*' => Http::response([], 503)]);

    $provider = new WalmartProvider;

    expect(fn () => $provider->search('headphones', [
        'account_sid' => 'test-account-sid',
        'auth_token' => 'test-auth-token',
    ]))->toThrow(ProviderException::class);
});
