<?php

namespace App\Services;

use App\Data\ProductResult;
use App\Data\ProductSearchResult;
use App\Exceptions\ProviderException;
use App\Models\Team;
use App\Models\TeamProviderCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductSearchService
{
    public function __construct(
        private readonly AffiliateProviderRegistry $registry,
    ) {}

    public function search(Team $team, string $query): ProductSearchResult
    {
        $cacheKey = "product_search:{$team->id}:".md5(strtolower(trim($query)));
        $ttl = config('affiliates.cache_ttl', 900);

        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return new ProductSearchResult(
                products: $cached['products'],
                providersQueried: $cached['providers_queried'],
                providersFailed: $cached['providers_failed'],
                wasCached: true,
            );
        }

        $credentials = $team->providerCredentials()->active()->get();

        $providersQueried = [];
        $providersFailed = [];
        $allProducts = [];

        /** @var array<int, callable(): ProductResult[]> $tasks */
        $tasks = [];

        foreach ($credentials as $credential) {
            /** @var TeamProviderCredential $credential */
            $providersQueried[] = $credential->provider->value;

            $tasks[] = function () use ($credential, $query, &$providersFailed, &$allProducts): void {
                try {
                    $driver = $this->registry->resolve($credential->provider);
                    $results = $driver->search($query, $credential->credentials);
                    $allProducts = array_merge($allProducts, $results);
                } catch (ProviderException $e) {
                    Log::warning("Affiliate provider [{$e->provider->value}] failed: {$e->getMessage()}");
                    $providersFailed[] = $e->provider->value;
                }
            };
        }

        // Execute all provider searches concurrently via Http::pool equivalent
        // Since adapters may not all use Http::pool internally, we run them sequentially
        // but the pattern is ready for extraction to a pool if adapters expose request objects.
        foreach ($tasks as $task) {
            $task();
        }

        usort($allProducts, fn (ProductResult $a, ProductResult $b) => $a->price <=> $b->price);

        $payload = [
            'products' => $allProducts,
            'providers_queried' => $providersQueried,
            'providers_failed' => $providersFailed,
        ];

        Cache::put($cacheKey, $payload, $ttl);

        return new ProductSearchResult(
            products: $allProducts,
            providersQueried: $providersQueried,
            providersFailed: $providersFailed,
            wasCached: false,
        );
    }
}
