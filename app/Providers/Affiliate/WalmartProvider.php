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

        $timestamp = (string) (int) (microtime(true) * 1000);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'WM_CONSUMER.ID' => $credentials['account_sid'],
                'WM_SEC.KEY_VERSION' => '1',
                'WM_CONSUMER.INTIMESTAMP' => $timestamp,
                'WM_SEC.AUTH_SIGNATURE' => $this->sign($credentials['account_sid'], $credentials['auth_token'], $timestamp),
                'Accept' => 'application/json',
            ])
            ->get("{$baseUrl}/api-proxy/service/affil/product/v2/search", [
                'publisherId' => $credentials['publisher_id'],
                'query' => $query,
                'format' => 'json',
            ]);

        if ($response->failed()) {
            $body = $response->body();
            throw new ProviderException(
                AffiliateProvider::Walmart,
                "Walmart API returned {$response->status()}: {$body}"
            );
        }

        return $this->mapResults($response->json('items', []));
    }

    /**
     * Builds the RSA-SHA256 signature per Walmart's canonicalization spec.
     * Header values are sorted by key name (ascending) and joined with "\n".
     * Sorted order: WM_CONSUMER.ID, WM_CONSUMER.INTIMESTAMP, WM_SEC.KEY_VERSION
     */
    private function sign(string $consumerId, string $privateKeyBase64, string $timestamp): string
    {
        $message = $consumerId."\n".$timestamp."\n1\n";
        $privateKey = base64_decode($privateKeyBase64);
        $signature = '';

        try {
            $signed = openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        } catch (\Throwable) {
            $signed = false;
        }

        if (! $signed || $signature === '') {
            throw new ProviderException($this->providerName(), 'Walmart signature generation failed. Check your private key.');
        }

        return base64_encode($signature);
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
