<?php

return [
    'cache_ttl' => env('AFFILIATE_CACHE_TTL', 900),

    'providers' => [
        'walmart' => [
            'base_url' => env('WALMART_API_BASE_URL', 'https://affiliate.api.walmart.com'),
            'timeout' => (int) env('WALMART_API_TIMEOUT', 5),
        ],
        'amazon' => [
            'base_url' => env('AMAZON_API_BASE_URL', 'https://webservices.amazon.com'),
            'timeout' => (int) env('AMAZON_API_TIMEOUT', 8),
            'region' => env('AMAZON_PA_REGION', 'us-east-1'),
        ],
    ],
];
