<?php

namespace App\Data;

readonly class ProductSearchResult
{
    /**
     * @param  ProductResult[]  $products
     * @param  string[]  $providersQueried
     * @param  string[]  $providersFailed
     */
    public function __construct(
        public array $products,
        public array $providersQueried,
        public array $providersFailed,
        public bool $wasCached,
    ) {}
}
