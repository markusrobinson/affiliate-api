<?php

namespace App\Data;

use App\Enums\AffiliateProvider;

readonly class ProductResult
{
    public function __construct(
        public string $id,
        public string $title,
        public float $price,
        public string $currency,
        public string $imageUrl,
        public string $affiliateUrl,
        public AffiliateProvider $provider,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'currency' => $this->currency,
            'image_url' => $this->imageUrl,
            'affiliate_url' => $this->affiliateUrl,
            'provider' => $this->provider->value,
        ];
    }
}
