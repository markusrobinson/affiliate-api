<?php

namespace App\Actions\Teams;

use App\Models\Team;
use Laravel\Sanctum\NewAccessToken;

class IssueApiToken
{
    public function handle(Team $team, string $name): NewAccessToken
    {
        return $team->createToken($name);
    }
}
