<?php

namespace App\Services;

use App\Contracts\ProductProvider;
use App\Enums\AffiliateProvider;
use InvalidArgumentException;

class AffiliateProviderRegistry
{
    /** @var array<string, ProductProvider> */
    private array $drivers = [];

    public function register(AffiliateProvider $provider, ProductProvider $driver): void
    {
        $this->drivers[$provider->value] = $driver;
    }

    public function resolve(AffiliateProvider $provider): ProductProvider
    {
        if (! isset($this->drivers[$provider->value])) {
            throw new InvalidArgumentException("No driver registered for provider [{$provider->value}].");
        }

        return $this->drivers[$provider->value];
    }

    /**
     * @return ProductProvider[]
     */
    public function all(): array
    {
        return array_values($this->drivers);
    }
}
