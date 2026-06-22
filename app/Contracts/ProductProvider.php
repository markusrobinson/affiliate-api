<?php

namespace App\Contracts;

use App\Data\ProductResult;
use App\Enums\AffiliateProvider;

interface ProductProvider
{
    /**
     * Search for products by keyword.
     *
     * @param  array<string, string>  $credentials
     * @return ProductResult[]
     */
    public function search(string $query, array $credentials): array;

    public function providerName(): AffiliateProvider;
}
