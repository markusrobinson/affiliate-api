<?php

namespace App\Actions\Teams;

use App\Enums\AffiliateProvider;
use App\Models\Team;
use App\Models\TeamProviderCredential;
use Illuminate\Validation\ValidationException;

class StoreProviderCredentials
{
    /**
     * @param  array<string, string>  $credentials
     *
     * @throws ValidationException
     */
    public function handle(Team $team, AffiliateProvider $provider, array $credentials): TeamProviderCredential
    {
        $required = $provider->requiredCredentialKeys();
        $missing = array_diff($required, array_keys(array_filter($credentials)));

        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'credentials' => ["Missing required credential keys: ".implode(', ', $missing)],
            ]);
        }

        /** @var TeamProviderCredential */
        return TeamProviderCredential::updateOrCreate(
            ['team_id' => $team->id, 'provider' => $provider->value],
            ['credentials' => $credentials, 'is_active' => true],
        );
    }
}
