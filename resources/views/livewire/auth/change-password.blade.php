<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('Reset Password') }}</flux:heading>

    <form wire:submit="changePassword" class="flex flex-col gap-6">
        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" placeholder="email@example.com" />

        <flux:input wire:model="password" label="{{ __('Password') }}" type="password" placeholder="{{ __('Your password') }}" />

        <flux:input wire:model="password_confirmation" label="{{ __('Confirm Password') }}" type="password" placeholder="{{ __('Confirm your password') }}" />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('Reset Password') }}</flux:button>
    </form>
</div>
