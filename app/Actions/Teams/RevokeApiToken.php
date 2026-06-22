<?php

namespace App\Actions\Teams;

use App\Models\Team;

class RevokeApiToken
{
    public function handle(Team $team, int $tokenId): void
    {
        $team->tokens()->where('id', $tokenId)->delete();
    }
}
