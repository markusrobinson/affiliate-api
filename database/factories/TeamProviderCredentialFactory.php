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
            'credentials' => $this->walmartCredentials(),
            'is_active' => true,
        ];
    }

    public function walmart(): static
    {
        return $this->state([
            'provider' => AffiliateProvider::Walmart,
            'credentials' => $this->walmartCredentials(),
        ]);
    }

    /** @return array<string, string> */
    private function walmartCredentials(): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);

        // Strip PEM headers/footers and whitespace — Walmart provides bare base64 DER
        $base64Der = preg_replace('/-----.*?-----|\s/', '', $pem);

        return [
            'account_sid' => fake()->uuid(),
            'auth_token' => $base64Der,
            'publisher_id' => fake()->numerify('#########'),
        ];
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
