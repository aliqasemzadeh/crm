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

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:card>
            <flux:text>{{ __('app.total_balance') }}</flux:text>
            <flux:heading size="xl" class="mb-1">{{ number_format($this->totalBalance, 0) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('app.usdt_rate') }}</flux:text>
            <flux:heading size="xl" class="mb-1">{{ number_format($this->usdtRate, 0) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('app.total_usdt_balance') }}</flux:text>
            <flux:heading size="xl" class="mb-1">{{ number_format($this->totalUSDTBalance, 2) }} {{ __('app.usdt') }}</flux:heading>
        </flux:card>
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
            <flux:table.column>{{ __('app.creator') }}</flux:table.column>
            <flux:table.column>{{ __('app.bank_balance') }}</flux:table.column>
            @can('administrator_access')
                <flux:table.column>{{ __('app.usdt_rate') }}</flux:table.column>
                <flux:table.column>{{ __('app.bank_balance') }} ({{ __('app.usdt') }})</flux:table.column>
            @endcan
            <flux:table.column>{{ __('app.last_modification_balance') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @php
                $bankTranslations = __('banks');
            @endphp
            @foreach ($bankAccounts as $bankAccount)
                @php
                    $bank = $bankAccount?->bankBranch?->bank;
                    $bankTitle = $bank?->Title;

                    $bankKey = null;
                    if ($bankTitle) {
                        foreach ($bankTranslations as $key => $value) {
                            if ($value === $bankTitle) {
                                $bankKey = $key;
                                break;
                            }
                        }
                    }

                    $logoUrl = $bankKey ? asset("images/banks/{$bankKey}.svg") : null;
                    $bankBalanceRial = $bankAccount->Balance;
                    $usdtBalance = $this->usdtRate > 0 ? $bankBalanceRial / $this->usdtRate : 0;
                @endphp
                <flux:table.row :key="$bankAccount->BankAccountId">
                    <flux:table.cell>
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $bankTitle }}" class="w-8 h-8 object-contain">
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="flex flex-col">
                        <flux:text class="font-medium text-zinc-800 dark:text-white">{{ $bankTitle }}</flux:text>
                        <flux:text size="sm" variant="subtle">{{ $bankAccount?->AccountNo }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $bankAccount?->creator?->Name ?? '-' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ number_format($bankBalanceRial, 0) }} {{ __('app.rial') }}
                    </flux:table.cell>
                    @can('administrator_access')
                        <flux:table.cell>
                            {{ number_format($this->usdtRate, 0) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ number_format($usdtBalance, 2) }} {{ __('app.usdt') }}
                        </flux:table.cell>
                    @endcan
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $bankAccount?->LastModificationDate ? \Morilog\Jalali\Jalalian::fromDateTime($bankAccount->LastModificationDate)->format('%Y-%m-%d %H:%M') : '-' }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">

                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
