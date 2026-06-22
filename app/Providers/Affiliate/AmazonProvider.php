<?php

namespace App\Providers\Affiliate;

use App\Contracts\ProductProvider;
use App\Data\ProductResult;
use App\Enums\AffiliateProvider;
use App\Exceptions\ProviderException;
use Illuminate\Support\Facades\Http;

class AmazonProvider implements ProductProvider
{
    public function providerName(): AffiliateProvider
    {
        return AffiliateProvider::Amazon;
    }

    /**
     * @param  array<string, string>  $credentials
     * @return ProductResult[]
     *
     * @throws ProviderException
     */
    public function search(string $query, array $credentials): array
    {
        $marketplace = $credentials['marketplace'] ?? 'www.amazon.com';
        $region = config('affiliates.providers.amazon.region', 'us-east-1');
        $timeout = config('affiliates.providers.amazon.timeout', 8);
        $endpoint = "https://webservices.amazon.com/paapi5/searchitems";

        $payload = [
            'Keywords' => $query,
            'PartnerTag' => $credentials['partner_tag'],
            'PartnerType' => 'Associates',
            'Marketplace' => $marketplace,
            'Resources' => [
                'Images.Primary.Large',
                'ItemInfo.Title',
                'Offers.Listings.Price',
            ],
        ];

        $headers = $this->buildSignedHeaders(
            $payload,
            $credentials,
            $region,
            $endpoint,
        );

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->post($endpoint, $payload);

        if ($response->failed()) {
            throw new ProviderException(
                AffiliateProvider::Amazon,
                "Amazon PA-API returned {$response->status()}"
            );
        }

        $items = $response->json('SearchResult.Items', []);

        return $this->mapResults($items, $credentials['partner_tag']);
    }

    /**
     * @param  array<string, mixed>  $items
     * @return ProductResult[]
     */
    private function mapResults(array $items, string $partnerTag): array
    {
        return array_values(array_filter(array_map(function (array $item) use ($partnerTag): ?ProductResult {
            $asin = $item['ASIN'] ?? null;
            $title = $item['ItemInfo']['Title']['DisplayValue'] ?? null;

            if (! $asin || ! $title) {
                return null;
            }

            $price = (float) ($item['Offers']['Listings'][0]['Price']['Amount'] ?? 0);
            $currency = $item['Offers']['Listings'][0]['Price']['Currency'] ?? 'USD';
            $imageUrl = $item['Images']['Primary']['Large']['URL'] ?? '';
            $affiliateUrl = "https://www.amazon.com/dp/{$asin}?tag={$partnerTag}";

            return new ProductResult(
                id: $asin,
                title: $title,
                price: $price,
                currency: $currency,
                imageUrl: $imageUrl,
                affiliateUrl: $affiliateUrl,
                provider: AffiliateProvider::Amazon,
            );
        }, $items)));
    }

    /**
     * Build AWS SigV4-style signed headers for PA-API 5.0.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $credentials
     * @return array<string, string>
     */
    private function buildSignedHeaders(
        array $payload,
        array $credentials,
        string $region,
        string $endpoint,
    ): array {
        $service = 'ProductAdvertisingAPI';
        $method = 'POST';
        $amzTarget = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems';
        $contentType = 'application/json; charset=UTF-8';
        $now = now()->utc();
        $amzDate = $now->format('Ymd\THis\Z');
        $dateStamp = $now->format('Ymd');

        $body = json_encode($payload) ?: '';
        $payloadHash = hash('sha256', $body);

        $canonicalHeaders =
            "content-encoding:amz-1.0\n".
            "content-type:{$contentType}\n".
            "host:webservices.amazon.com\n".
            "x-amz-date:{$amzDate}\n".
            "x-amz-target:{$amzTarget}\n";

        $signedHeaders = 'content-encoding;content-type;host;x-amz-date;x-amz-target';

        $canonicalRequest = implode("\n", [
            $method,
            '/paapi5/searchitems',
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->deriveSigningKey($credentials['secret_key'], $dateStamp, $region, $service);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $authorization =
            "AWS4-HMAC-SHA256 Credential={$credentials['access_key']}/{$credentialScope}, ".
            "SignedHeaders={$signedHeaders}, Signature={$signature}";

        return [
            'Authorization' => $authorization,
            'Content-Encoding' => 'amz-1.0',
            'Content-Type' => $contentType,
            'Host' => 'webservices.amazon.com',
            'X-Amz-Date' => $amzDate,
            'X-Amz-Target' => $amzTarget,
        ];
    }

    private function deriveSigningKey(
        string $secretKey,
        string $dateStamp,
        string $region,
        string $service,
    ): string {
        $kDate = hash_hmac('sha256', $dateStamp, "AWS4{$secretKey}", true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);

        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
