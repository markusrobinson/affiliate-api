<?php

use App\Actions\Teams\IssueApiToken;
use App\Actions\Teams\RevokeApiToken;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('API Tokens')] class extends Component {
    public string $tokenName = '';
    public ?string $newTokenValue = null;

    public function issueToken(IssueApiToken $action): void
    {
        $this->validate(['tokenName' => ['required', 'string', 'max:100']]);

        $team = Auth::user()->currentTeam;
        $newToken = $action->handle($team, $this->tokenName);

        $this->newTokenValue = $newToken->plainTextToken;
        $this->tokenName = '';

        Flux::toast(variant: 'success', text: __('Token created. Copy it now — it will not be shown again.'));
    }

    public function revokeToken(RevokeApiToken $action, int $tokenId): void
    {
        $team = Auth::user()->currentTeam;
        $action->handle($team, $tokenId);

        $this->newTokenValue = null;

        Flux::toast(variant: 'success', text: __('Token revoked.'));
    }

    public function dismissToken(): void
    {
        $this->newTokenValue = null;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('API Tokens')" :subheading="__('Manage tokens that allow external applications to search affiliate products for this team.')">

        @if ($newTokenValue)
            <flux:callout variant="success" icon="check-circle" class="my-6">
                <flux:callout.heading>{{ __('Token created') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Copy this token now. It will not be shown again.') }}
                </flux:callout.text>
                <flux:callout.actions>
                    <flux:input value="{{ $newTokenValue }}" readonly />
                    <flux:button wire:click="dismissToken" variant="ghost">{{ __('Dismiss') }}</flux:button>
                </flux:callout.actions>
            </flux:callout>
        @endif

        <form wire:submit="issueToken" class="my-6 flex items-end gap-3">
            <flux:input wire:model="tokenName" :label="__('Token name')" type="text" placeholder="e.g. Editorial CMS" class="flex-1" />
            <flux:button variant="primary" type="submit">{{ __('Create token') }}</flux:button>
        </form>

        @php $tokens = Auth::user()->currentTeam->tokens()->latest()->get(); @endphp

        @if ($tokens->isEmpty())
            <p class="text-sm text-zinc-500">{{ __('No API tokens yet.') }}</p>
        @else
            <flux:table>
                <flux:table.head>
                    <flux:table.row>
                        <flux:table.cell>{{ __('Name') }}</flux:table.cell>
                        <flux:table.cell>{{ __('Created') }}</flux:table.cell>
                        <flux:table.cell>{{ __('Last used') }}</flux:table.cell>
                        <flux:table.cell></flux:table.cell>
                    </flux:table.row>
                </flux:table.head>
                <flux:table.body>
                    @foreach ($tokens as $token)
                        <flux:table.row>
                            <flux:table.cell>{{ $token->name }}</flux:table.cell>
                            <flux:table.cell>{{ $token->created_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell>{{ $token->last_used_at?->diffForHumans() ?? __('Never') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button wire:click="revokeToken({{ $token->id }})" variant="ghost" size="sm">
                                    {{ __('Revoke') }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.body>
            </flux:table>
        @endif

    </x-pages::settings.layout>
</section>
