<?php

use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.user')] class extends Component
{
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        Flux::toast(__('app.password_updated_successfully'));
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl" level="1">{{ __('app.change_password') }}</flux:heading>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <flux:input wire:model="current_password" label="{{ __('app.current_password') }}" type="password" viewable />

        <flux:input wire:model="new_password" label="{{ __('app.new_password') }}" type="password" viewable />

        <flux:input wire:model="new_password_confirmation" label="{{ __('app.password_confirmation') }}" type="password" viewable />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
        </div>
    </form>
</div>
