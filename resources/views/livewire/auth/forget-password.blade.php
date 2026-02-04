<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('Reset Password') }}</flux:heading>

    <form wire:submit="sendResetCode" class="flex flex-col gap-6">
        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" placeholder="email@example.com" />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('Send') }}</flux:button>
    </form>

    <flux:subheading class="text-center">
        <flux:link href="{{ route('login') }}" wire:navigate>{{ __('Back to login') }}</flux:link>
    </flux:subheading>
</div>
