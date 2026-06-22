<?php

use App\Actions\Teams\RevokeProviderCredentials;
use App\Actions\Teams\StoreProviderCredentials;
use App\Enums\AffiliateProvider;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Affiliate Providers')] class extends Component {
    /** @var array<string, array<string, string>> */
    public array $credentials = [];

    public function mount(): void
    {
        foreach (AffiliateProvider::cases() as $provider) {
            foreach ($provider->requiredCredentialKeys() as $key) {
                $this->credentials[$provider->value][$key] = '';
            }
        }
    }

    public function saveProvider(StoreProviderCredentials $action, string $providerValue): void
    {
        $provider = AffiliateProvider::from($providerValue);

        $action->handle(
            team: Auth::user()->currentTeam,
            provider: $provider,
            credentials: $this->credentials[$providerValue],
        );

        foreach ($provider->requiredCredentialKeys() as $key) {
            $this->credentials[$providerValue][$key] = '';
        }

        Flux::toast(variant: 'success', text: __(':provider credentials saved.', ['provider' => $provider->label()]));
    }

    public function revokeProvider(RevokeProviderCredentials $action, string $providerValue): void
    {
        $provider = AffiliateProvider::from($providerValue);
        $action->handle(Auth::user()->currentTeam, $provider);

        Flux::toast(variant: 'warning', text: __(':provider disconnected.', ['provider' => $provider->label()]));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Affiliate Providers')" :subheading="__('Connect your affiliate accounts. Credentials are encrypted and stored per team.')">

        @php
            $team = Auth::user()->currentTeam;
            $saved = $team->providerCredentials()->get()->keyBy(fn ($c) => $c->provider->value);
        @endphp

        <div class="my-6 space-y-8">
            @foreach (AffiliateProvider::cases() as $provider)
                @php $isSaved = isset($saved[$provider->value]) && $saved[$provider->value]->is_active; @endphp

                <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                    <div class="mb-4 flex items-center justify-between">
                        <flux:heading size="sm">{{ $provider->label() }}</flux:heading>
                        @if ($isSaved)
                            <flux:badge variant="success">{{ __('Connected') }}</flux:badge>
                        @endif
                    </div>

                    <form wire:submit="saveProvider('{{ $provider->value }}')" class="space-y-4">
                        @foreach ($provider->requiredCredentialKeys() as $key)
                            <flux:input
                                wire:model="credentials.{{ $provider->value }}.{{ $key }}"
                                :label="ucwords(str_replace('_', ' ', $key))"
                                type="password"
                                :placeholder="$isSaved ? __('Leave blank to keep existing value') : ''"
                                autocomplete="off"
                            />
                        @endforeach

                        <div class="flex items-center gap-3">
                            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                            @if ($isSaved)
                                <flux:button wire:click="revokeProvider('{{ $provider->value }}')" variant="ghost" wire:confirm="{{ __('Disconnect this provider?') }}">
                                    {{ __('Disconnect') }}
                                </flux:button>
                            @endif
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

    </x-pages::settings.layout>
</section>
