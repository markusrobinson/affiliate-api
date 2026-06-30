<?php

namespace App\Providers\Affiliate;

use App\Contracts\ProductProvider;
use App\Data\ProductResult;
use App\Enums\AffiliateProvider;
use App\Exceptions\ProviderException;
use Illuminate\Support\Facades\Http;

class WalmartProvider implements ProductProvider
{
    public function providerName(): AffiliateProvider
    {
        return AffiliateProvider::Walmart;
    }

    /**
     * @param  array<string, string>  $credentials
     * @return ProductResult[]
     *
     * @throws ProviderException
     */
    public function search(string $query, array $credentials): array
    {
        $baseUrl = config('affiliates.providers.walmart.base_url');
        $timeout = config('affiliates.providers.walmart.timeout', 5);

        $response = Http::timeout($timeout)
            ->withBasicAuth($credentials['account_sid'], $credentials['auth_token'])
            ->withHeaders(['Accept' => 'application/json'])
            ->get("{$baseUrl}/v1/search", [
                'query' => $query,
                'format' => 'json',
            ]);

        if ($response->failed()) {
            throw new ProviderException(
                AffiliateProvider::Walmart,
                "Walmart API returned {$response->status()}"
            );
        }

        return $this->mapResults($response->json('items', []));
    }

    /**
     * @param  array<string, mixed>  $items
     * @return ProductResult[]
     */
    private function mapResults(array $items): array
    {
        return array_values(array_filter(array_map(function (array $item): ?ProductResult {
            if (empty($item['itemId']) || empty($item['name'])) {
                return null;
            }

            return new ProductResult(
                id: (string) $item['itemId'],
                title: $item['name'],
                price: (float) ($item['salePrice'] ?? $item['msrp'] ?? 0),
                currency: 'USD',
                imageUrl: $item['largeImage'] ?? $item['thumbnailImage'] ?? '',
                affiliateUrl: $item['affiliateAddToCartUrl'] ?? $item['productUrl'] ?? '',
                provider: AffiliateProvider::Walmart,
            );
        }, $items)));
    }
}
