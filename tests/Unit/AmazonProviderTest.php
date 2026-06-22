<?php

use App\Enums\AffiliateProvider;
use Tests\TestCase;

uses(TestCase::class);
use App\Exceptions\ProviderException;
use App\Providers\Affiliate\AmazonProvider;
use Illuminate\Support\Facades\Http;

it('maps amazon search response to ProductResult array', function (): void {
    Http::fake([
        'webservices.amazon.com/*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/Amazon/search-response.json')), true),
        ),
    ]);

    $provider = new AmazonProvider;
    $results = $provider->search('headphones', [
        'access_key' => 'AKIAIOSFODNN7EXAMPLE',
        'secret_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
        'partner_tag' => 'mytag-20',
        'marketplace' => 'www.amazon.com',
    ]);

    expect($results)->toHaveCount(2)
        ->and($results[0]->provider)->toBe(AffiliateProvider::Amazon)
        ->and($results[0]->id)->toBe('B09XS7JWHH')
        ->and($results[0]->price)->toBe(298.0)
        ->and($results[0]->affiliateUrl)->toContain('mytag-20');
});

it('throws ProviderException on non-2xx response', function (): void {
    Http::fake(['webservices.amazon.com/*' => Http::response([], 429)]);

    $provider = new AmazonProvider;

    expect(fn () => $provider->search('headphones', [
        'access_key' => 'key',
        'secret_key' => 'secret',
        'partner_tag' => 'tag-20',
        'marketplace' => 'www.amazon.com',
    ]))->toThrow(ProviderException::class);
});
