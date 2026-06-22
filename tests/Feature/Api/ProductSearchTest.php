<?php

use App\Enums\AffiliateProvider;
use App\Models\Team;
use App\Models\TeamProviderCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('returns 401 when unauthenticated', function (): void {
    $this->getJson('/api/v1/products/search?q=headphones')
        ->assertStatus(401);
});

it('returns 401 with an invalid token', function (): void {
    $this->withToken('invalid-token')
        ->getJson('/api/v1/products/search?q=headphones')
        ->assertStatus(401);
});

it('returns 422 when query is missing', function (): void {
    $team = Team::factory()->create();
    $token = $team->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/products/search')
        ->assertStatus(422)
        ->assertJsonValidationErrors('q');
});

it('returns 422 when query is too short', function (): void {
    $team = Team::factory()->create();
    $token = $team->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/products/search?q=a')
        ->assertStatus(422)
        ->assertJsonValidationErrors('q');
});

it('returns unified normalized results from configured providers', function (): void {
    Http::fake([
        'affiliate.api.walmart.com/*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/Walmart/search-response.json')), true),
        ),
        'webservices.amazon.com/*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/Amazon/search-response.json')), true),
        ),
    ]);

    $team = Team::factory()->create();
    $token = $team->createToken('test')->plainTextToken;

    TeamProviderCredential::factory()->walmart()->for($team)->create();
    TeamProviderCredential::factory()->amazon()->for($team)->create();

    $response = $this->withToken($token)
        ->getJson('/api/v1/products/search?q=headphones')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'price', 'currency', 'image_url', 'affiliate_url', 'provider'],
            ],
            'meta' => ['query', 'total', 'providers_queried', 'providers_failed', 'cached'],
        ]);

    expect($response->json('meta.cached'))->toBeFalse();
    expect($response->json('meta.providers_failed'))->toBeEmpty();
    expect($response->json('meta.total'))->toBeGreaterThan(0);
});

it('returns partial results when one provider fails', function (): void {
    Http::fake([
        'affiliate.api.walmart.com/*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/Walmart/search-response.json')), true),
        ),
        'webservices.amazon.com/*' => Http::response([], 500),
    ]);

    $team = Team::factory()->create();
    $token = $team->createToken('test')->plainTextToken;

    TeamProviderCredential::factory()->walmart()->for($team)->create();
    TeamProviderCredential::factory()->amazon()->for($team)->create();

    $response = $this->withToken($token)
        ->getJson('/api/v1/products/search?q=headphones')
        ->assertOk();

    expect($response->json('meta.providers_failed'))->toContain('amazon');
    expect($response->json('meta.total'))->toBeGreaterThan(0);
});

it('serves results from cache on second request', function (): void {
    Http::fake([
        'affiliate.api.walmart.com/*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/Walmart/search-response.json')), true),
        ),
    ]);

    $team = Team::factory()->create();
    $token = $team->createToken('test')->plainTextToken;
    TeamProviderCredential::factory()->walmart()->for($team)->create();

    $this->withToken($token)->getJson('/api/v1/products/search?q=headphones')->assertOk();

    Http::fake(['*' => Http::response([], 500)]); // subsequent HTTP should not fire

    $response = $this->withToken($token)
        ->getJson('/api/v1/products/search?q=headphones')
        ->assertOk();

    expect($response->json('meta.cached'))->toBeTrue();
});

it('returns empty results when team has no configured providers', function (): void {
    $team = Team::factory()->create();
    $token = $team->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/products/search?q=headphones')
        ->assertOk();

    expect($response->json('meta.total'))->toBe(0);
    expect($response->json('meta.providers_queried'))->toBeEmpty();
});
