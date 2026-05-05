<?php

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.user')] class extends Component
{
    public string $current_password = '';
    public string $new_mobile = '';

    public function updateMobile()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_mobile' => [
                'required',
                'string',
                'ir_mobile',
                'max:255',
                Rule::unique('users', 'mobile')->ignore(auth()->id()),
            ],
        ]);

        auth()->user()->update([
            'mobile' => $this->new_mobile,
        ]);

        $this->reset(['current_password', 'new_mobile']);

        Flux::toast(__('app.mobile_updated_successfully'));
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl" level="1">{{ __('app.change_mobile') }}</flux:heading>

    <form wire:submit="updateMobile" class="mt-6 space-y-6">
        <flux:input wire:model="current_password" label="{{ __('app.current_password') }}" type="password" viewable />

        <flux:input wire:model="new_mobile" label="{{ __('app.mobile') }}" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
        </div>
    </form>
</div>
