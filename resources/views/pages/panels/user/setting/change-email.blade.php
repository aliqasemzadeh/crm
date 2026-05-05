<?php

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.user')] class extends Component
{
    public string $current_password = '';
    public string $new_email = '';

    public function updateEmail()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
        ]);

        auth()->user()->update([
            'email' => $this->new_email,
        ]);

        $this->reset(['current_password', 'new_email']);

        Flux::toast(__('app.email_updated_successfully'));
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl" level="1">{{ __('app.change_email') }}</flux:heading>

    <form wire:submit="updateEmail" class="mt-6 space-y-6">
        <flux:input wire:model="current_password" label="{{ __('app.current_password') }}" type="password" viewable />

        <flux:input wire:model="new_email" label="{{ __('app.email') }}" type="email" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
        </div>
    </form>
</div>
