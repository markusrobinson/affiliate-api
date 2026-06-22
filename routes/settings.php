<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    Route::livewire('settings/api-tokens', 'pages::settings.api-tokens')->name('api-tokens.index');
    Route::livewire('settings/provider-credentials', 'pages::settings.provider-credentials')->name('provider-credentials.index');

    Route::livewire('settings/teams', 'pages::teams.index')->name('teams.index');

    Route::middleware(EnsureTeamMembership::class)->group(function () {
        Route::livewire('settings/teams/{team}', 'pages::teams.edit')->name('teams.edit');
    });
});
