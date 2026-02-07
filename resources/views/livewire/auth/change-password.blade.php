<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('app.reset_password') }}</flux:heading>

    <form wire:submit="changePassword" class="flex flex-col gap-6">
        <flux:input wire:model="email" label="{{ __('app.email') }}" type="email" placeholder="email@example.com" />

        <flux:input wire:model="password" label="{{ __('app.password') }}" type="password" placeholder="{{ __('app.password_placeholder') }}" />

        <flux:input wire:model="password_confirmation" label="{{ __('app.password_confirmation') }}" type="password" placeholder="{{ __('app.confirm_password_placeholder') }}" />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('app.reset_password') }}</flux:button>
    </form>
</div>
