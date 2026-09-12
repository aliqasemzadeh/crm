<?php

use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.user')] class extends Component
{
    #[Computed]
    public function isConnected(): bool
    {
        return filled(auth()->user()?->bale_code);
    }

    public function connect(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        if (filled($user->bale_code)) {
            Flux::toast(__('app.bale_already_connected'));

            return;
        }

        $token = Str::random(32);

        Cache::put('bale:link:'.$token, $user->id, now()->addMinutes(15));

        $botName = (string) config('bale.crm_bot_name', 'SetareganCRMBot');
        $url = 'https://ble.ir/'.$botName.'?start='.$token;

        $this->dispatch('bale-open-link', url: $url);

        Flux::toast(__('app.bale_connect_opened'));
    }

    public function checkConnection(): void
    {
        unset($this->isConnected);

        auth()->user()?->refresh();

        if (filled(auth()->user()?->bale_code)) {
            Flux::toast(__('app.bale_connected_successfully'));
        } else {
            Flux::toast(__('app.bale_not_connected_yet'));
        }
    }

    public function disconnect(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $user->bale_code = null;
        $user->save();

        unset($this->isConnected);

        Flux::toast(__('app.bale_disconnected_successfully'));
    }
};
?>

<div
    class="max-w-xl"
    x-data
    x-on:bale-open-link.window="window.open($event.detail.url, '_blank')"
>
    <flux:heading size="xl" level="1">{{ __('app.bale_connection') }}</flux:heading>
    <flux:text class="mt-2">{{ __('app.bale_connection_description') }}</flux:text>

    <div class="mt-6 space-y-4">
        @if ($this->isConnected)
            <flux:callout variant="success" icon="circle-check">
                <flux:callout.heading>{{ __('app.bale_connected') }}</flux:callout.heading>
                <flux:callout.text>{{ __('app.bale_connected_hint') }}</flux:callout.text>
            </flux:callout>

            <flux:button
                type="button"
                variant="primary"
                color="red"
                class="w-full"
                wire:click="disconnect"
                wire:confirm="{{ __('app.are_you_sure') }}"
            >
                {{ __('app.bale_disconnect') }}
            </flux:button>
        @else
            <flux:callout icon="info">
                <flux:callout.heading>{{ __('app.bale_not_connected') }}</flux:callout.heading>
                <flux:callout.text>{{ __('app.bale_connect_hint') }}</flux:callout.text>
            </flux:callout>

            <flux:button
                type="button"
                variant="primary"
                color="teal"
                class="w-full"
                wire:click="connect"
            >
                {{ __('app.bale_connect') }}
            </flux:button>

            <flux:button
                type="button"
                variant="filled"
                color="zinc"
                class="w-full"
                wire:click="checkConnection"
            >
                {{ __('app.bale_check_connection') }}
            </flux:button>
        @endif
    </div>
</div>
