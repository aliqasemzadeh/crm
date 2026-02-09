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
