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
