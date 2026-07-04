<div xmlns:flux="http://www.w3.org/1999/html">
    <div class="relative mb-6 w-full">
        <x-slot name="title">
            {{ __('app.function') }}
        </x-slot>
        <flux:heading size="xl" level="1">{{ __('app.function') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.function_description') }}</flux:subheading>
        <flux:separator variant="subtle" />

        <div class="space-y-4">
            <flux:button wire:click="updatePermissions" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.update_permissions') }}</flux:button>
            <flux:button wire:click="clearCache" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.clear_cache') }}</flux:button>
            <flux:button wire:click="updateProject" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.update_project') }}</flux:button>
            <flux:button wire:click="runDayCheck" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.day_check.run_command') }}</flux:button>
            <flux:button wire:click="runPriceNotification" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.run_price_notification') }}</flux:button>
            <flux:button wire:click="runRecurringTasks" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.run_recurring_tasks') }}</flux:button>
            <flux:button wire:click="runFollowUp" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.run_follow_up') }}</flux:button>
            <flux:button wire:click="runInvoiceAlert" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.run_invoice_alert') }}</flux:button>
            <flux:button wire:click="runImportPhones" wire:confirm="{{ __('app.confirm_action') }}" class="w-full">{{ __('app.run_import_phones') }}</flux:button>
        </div>


    </div>
</div>
