<x-slot name="title">
    {{ __('app.banks') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.banks') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.banks_description') }}</flux:subheading>
            </div>
            <div class="flex gap-2">
                @can('accounting_bank_create')
                    <flux:modal.trigger name="accounting.bank.create.modal">
                        <flux:button variant="primary">{{ __('app.create_bank') }}</flux:button>
                    </flux:modal.trigger>
                @endcan
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6">
        <flux:text>{{ __('app.total_balance') }}</flux:text>
        <flux:heading size="xl" class="mb-1">{{ number_format($this->totalBalance, 0) }} {{ __('app.toman') }}</flux:heading>
    </div>

    <flux:table>
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="5" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.logo') }}</flux:table.column>
            <flux:table.column>{{ __('app.bank_name') }}</flux:table.column>
            <flux:table.column>{{ __('app.bank_balance') }}</flux:table.column>
            <flux:table.column>{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($bankAccounts as $bank)
                <flux:table.row :key="$bank['BankAccountId']">
                    <flux:table.cell>
                    </flux:table.cell>
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $bank['Owner'] }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ number_format($bank['Balance'], 0) }} {{ __('app.toman') }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ \Morilog\Jalali\Jalalian::fromDateTime($bank['LastModificationDate'])->format('%Y-%m-%d %H:%M') }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">

                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>