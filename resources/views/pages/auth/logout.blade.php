<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Flux\Flux;

new #[Layout('layouts.auth')] class extends Component
{
    public function logout()
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        Flux::toast(
            text: __('app.logout.success'),
            variant: 'success',
        );

        return $this->redirectRoute('login', navigate: true);
    }

    public function cancel()
    {
        return $this->redirect(url()->previous(), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('app.logout.message_line_1') }}</flux:heading>

    <div class="flex flex-col gap-3">
        <flux:button wire:click="logout" variant="primary" color="red" class="w-full">
            {{ __('app.logout.confirm') }}
        </flux:button>

        <flux:button wire:click="cancel" variant="ghost" class="w-full">
            {{ __('app.logout.cancel') }}
        </flux:button>
    </div>
</div>
