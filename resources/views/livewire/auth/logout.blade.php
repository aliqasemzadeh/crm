<div class="space-y-6">
    <flux:heading class="text-center" size="xl">{{ __('app.logout.message_line_1') }}</flux:heading>

    <div class="flex flex-col gap-3">
        <flux:button wire:click="logout" variant="primary" class="w-full">
            {{ __('app.logout.confirm') }}
        </flux:button>

        <flux:button wire:click="cancel" variant="ghost" class="w-full">
            {{ __('app.logout.cancel') }}
        </flux:button>
    </div>
</div>
