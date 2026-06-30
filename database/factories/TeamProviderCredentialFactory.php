<?php

namespace Database\Factories;

use App\Enums\AffiliateProvider;
use App\Models\Team;
use App\Models\TeamProviderCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamProviderCredential>
 */
class TeamProviderCredentialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'provider' => AffiliateProvider::Walmart,
            'credentials' => [
                'account_sid' => fake()->uuid(),
                'auth_token' => fake()->sha256(),
            ],
            'is_active' => true,
        ];
    }

    public function walmart(): static
    {
        return $this->state([
            'provider' => AffiliateProvider::Walmart,
            'credentials' => [
                'account_sid' => fake()->uuid(),
                'auth_token' => fake()->sha256(),
            ],
        ]);
    }

    public function amazon(): static
    {
        return $this->state([
            'provider' => AffiliateProvider::Amazon,
            'credentials' => [
                'access_key' => fake()->regexify('[A-Z0-9]{20}'),
                'secret_key' => fake()->sha256(),
                'partner_tag' => 'mytag-20',
                'marketplace' => 'www.amazon.com',
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
