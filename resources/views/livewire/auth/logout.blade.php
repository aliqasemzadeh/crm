<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('main.logout_confirmation') }}</flux:heading>

    <div class="flex flex-col gap-3">
        <flux:button wire:click="logout" variant="primary" class="w-full">
            {{ __('main.logout_confirm_yes') }}
        </flux:button>

        <flux:button wire:click="cancel" variant="ghost" class="w-full">
            {{ __('main.logout_confirm_no') }}
        </flux:button>
    </div>
</div>
