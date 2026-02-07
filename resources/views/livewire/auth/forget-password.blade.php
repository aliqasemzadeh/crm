<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('app.reset_password') }}</flux:heading>

    <form wire:submit="sendResetCode" class="flex flex-col gap-6">
        <flux:input wire:model="email" label="{{ __('app.email') }}" type="email" placeholder="email@example.com" />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('app.send') }}</flux:button>
    </form>

    <flux:subheading class="text-center">
        <flux:link href="{{ route('login') }}" wire:navigate>{{ __('app.back_to_login') }}</flux:link>
    </flux:subheading>
</div>
