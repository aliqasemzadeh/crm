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

            <flux:modal.trigger name="panels.administrator.setting-management.function.artisan-command">
                <flux:button
                    variant="primary"
                    color="teal"
                    icon="terminal"
                    class="w-full sm:w-auto"
                >
                    {{ __('app.artisan_command_palette') }}
                </flux:button>
            </flux:modal.trigger>
        </flux:card>

        <flux:modal
            name="panels.administrator.setting-management.function.artisan-command"
            variant="bare"
            class="w-full max-w-[36rem] my-[12vh] max-h-screen overflow-y-hidden"
        >
            <flux:command class="border-none shadow-lg inline-flex flex-col max-h-[76vh]">
                <flux:command.input
                    placeholder="{{ __('app.artisan_search_placeholder') }}"
                    closable
                />

                <flux:command.items>
                    @foreach ($this->artisanCommands as $command)
                        <flux:command.item
                            wire:key="artisan-cmd-{{ $command['name'] }}"
                            icon="terminal"
                            wire:click="runArtisanCommand('{{ $command['name'] }}')"
                            wire:confirm="{{ __('app.confirm_action') }}"
                            class="cursor-pointer"
                        >
                            <div class="flex min-w-0 flex-col">
                                <span class="font-mono font-medium">{{ $command['name'] }}</span>
                                @if ($command['description'] !== '')
                                    <span class="truncate text-xs text-zinc-500">{{ $command['description'] }}</span>
                                @endif
                            </div>
                        </flux:command.item>
                    @endforeach
                </flux:command.items>
            </flux:command>
        </flux:modal>

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
                    wire:click="updateProject(true)"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.update_project_full') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="yellow"
                    icon="zap"
                    wire:click="updateProject(false)"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.update_project_quick') }}
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
                <flux:button
                    variant="primary"
                    color="fuchsia"
                    icon="send"
                    wire:click="sendCrmBaleTestMessage"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.send_crm_bale_test') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    color="pink"
                    icon="shopping-bag"
                    wire:click="runPaidOrderBaleNotification"
                    wire:confirm="{{ __('app.confirm_action') }}"
                    class="w-full"
                >
                    {{ __('app.run_paid_order_bale_notification') }}
                </flux:button>
            </div>
        </flux:card>

        @if ($commandOutput !== '')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.artisan_output') }}</flux:heading>
                <pre dir="ltr" class="max-h-96 overflow-auto rounded-lg bg-zinc-950 p-4 text-left font-mono text-sm whitespace-pre-wrap break-words text-zinc-100">{{ $commandOutput }}</pre>
            </flux:card>
        @endif

        <flux:card class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('app.system_action_logs') }}</flux:heading>
                <flux:tooltip content="{{ __('app.refresh') }}">
                    <flux:button
                        size="xs"
                        variant="primary"
                        color="sky"
                        icon="refresh-cw"
                        icon:variant="outline"
                        wire:click="$refresh"
                    />
                </flux:tooltip>
            </div>

            <flux:table :paginate="$this->actionLogs">
                <flux:table.columns>
                    <flux:table.column>{{ __('app.artisan_command') }}</flux:table.column>
                    <flux:table.column>{{ __('app.status') }}</flux:table.column>
                    <flux:table.column>{{ __('app.artisan_output') }}</flux:table.column>
                    <flux:table.column>{{ __('app.date') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->actionLogs as $log)
                        <flux:table.row :key="$log->id">
                            <flux:table.cell class="font-mono text-sm">{{ $log->command }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColor = match ($log->status) {
                                        'success' => 'green',
                                        'failed' => 'red',
                                        'running' => 'amber',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$statusColor">
                                    {{ __('app.system_action_log_status.'.$log->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate font-mono text-xs" title="{{ $log->output }}">
                                {{ \Illuminate\Support\Str::limit($log->output ?? '—', 80) }}
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">
                                {{ \Morilog\Jalali\Jalalian::fromDateTime($log->created_at)->format('Y/m/d H:i') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="py-4 text-center">
                                {{ __('app.no_records_found') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
