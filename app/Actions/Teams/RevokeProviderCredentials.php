<?php

namespace App\Actions\Teams;

use App\Enums\AffiliateProvider;
use App\Models\Team;

class RevokeProviderCredentials
{
    public function handle(Team $team, AffiliateProvider $provider): void
    {
        $team->providerCredentials()
            ->where('provider', $provider->value)
            ->update(['is_active' => false]);
    }
}
