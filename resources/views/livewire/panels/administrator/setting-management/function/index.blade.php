<div xmlns:flux="http://www.w3.org/1999/html">
    <div class="relative mb-6 w-full space-y-6">
        <x-slot name="title">
            {{ __('app.function') }}
        </x-slot>
        <flux:heading size="xl" level="1">{{ __('app.function') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.function_description') }}</flux:subheading>
        <flux:separator variant="subtle" />

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('app.artisan_runner') }}</flux:heading>
            <flux:subheading>{{ __('app.artisan_runner_description') }}</flux:subheading>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <flux:input
                        wire:model="artisanCommand"
                        wire:keydown.enter="runArtisanCommand"
                        :label="__('app.artisan_command')"
                        placeholder="{{ __('app.artisan_command_placeholder') }}"
                        class="font-mono"
                    />
                </div>
                <flux:button
                    variant="primary"
                    color="teal"
                    icon="terminal"
                    wire:click="runArtisanCommand"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full sm:w-auto"
                >
                    {{ __('app.run') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('app.function_group_system') }}</flux:heading>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <flux:button
                    variant="primary"
                    color="indigo"
                    icon="shield"
                    wire:click="updatePermissions"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.update_permissions') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="amber"
                    icon="trash"
                    wire:click="clearCache"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.clear_cache') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="orange"
                    icon="refresh-cw"
                    wire:click="updateProject"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.update_project') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('app.function_group_jobs') }}</flux:heading>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <flux:button
                    variant="primary"
                    color="teal"
                    icon="calendar-check"
                    wire:click="runDayCheck"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.day_check.run_command') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="sky"
                    icon="megaphone"
                    wire:click="runPriceNotification"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.run_price_notification') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="green"
                    icon="repeat"
                    wire:click="runRecurringTasks"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.run_recurring_tasks') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="cyan"
                    icon="phone-call"
                    wire:click="runFollowUp"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.run_follow_up') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="violet"
                    icon="triangle-alert"
                    wire:click="runInvoiceAlert"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.run_invoice_alert') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="blue"
                    icon="import"
                    wire:click="runImportPhones"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.run_import_phones') }}
                </flux:button>
            </div>
        </flux:card>

        @if ($commandOutput !== '')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.artisan_output') }}</flux:heading>
                <pre dir="ltr" class="max-h-96 overflow-auto rounded-lg bg-zinc-950 p-4 text-left font-mono text-sm whitespace-pre-wrap break-words text-zinc-100">{{ $commandOutput }}</pre>
            </flux:card>
        @endif
    </div>
</div>
