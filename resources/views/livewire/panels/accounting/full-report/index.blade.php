<div class="space-y-6">
    <div class="flex justify-between items-center">
        <flux:heading size="xl">{{ __('app.full_account_report') }}</flux:heading>
        <div class="w-1/3">
            <flux:input wire:model.live="search" icon="calculator" placeholder="{{ __('app.search_placeholder') }}" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="flex flex-col items-center justify-center p-4 bg-red-50 dark:bg-red-950/20">
            <flux:text class="text-red-600 dark:text-red-400 font-bold">{{ __('app.debit') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->totals['debit']) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-col items-center justify-center p-4 bg-green-50 dark:bg-green-950/20">
            <flux:text class="text-green-600 dark:text-green-400 font-bold">{{ __('app.credit') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->totals['credit']) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-col items-center justify-center gap-1 p-4 bg-red-50 dark:bg-red-950/20">
            <flux:text class="text-red-600 dark:text-red-400 font-bold">{{ __('app.aggregate_debtor_balance_label') }}</flux:text>
            <flux:text size="sm" class="text-red-600/80 dark:text-red-400/80 text-center">{{ __('app.aggregate_debtor_balance_hint') }}</flux:text>
            <flux:heading size="lg" class="text-red-700 dark:text-red-300">
                {{ number_format($this->finalBalanceSplits['debtor_total']) }} {{ __('app.rial') }}
            </flux:heading>
        </flux:card>
        <flux:card class="flex flex-col items-center justify-center gap-1 p-4 bg-green-50 dark:bg-green-950/20">
            <flux:text class="text-green-600 dark:text-green-400 font-bold">{{ __('app.aggregate_creditor_balance_label') }}</flux:text>
            <flux:text size="sm" class="text-green-600/80 dark:text-green-400/80 text-center">{{ __('app.aggregate_creditor_balance_hint') }}</flux:text>
            <flux:heading size="lg" class="text-green-700 dark:text-green-300">
                @if($this->finalBalanceSplits['creditor_total'] > 0)
                    ({{ number_format($this->finalBalanceSplits['creditor_total']) }}) {{ __('app.rial') }}
                @else
                    {{ number_format(0) }} {{ __('app.rial') }}
                @endif
            </flux:heading>
        </flux:card>
    </div>

    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.debit') }}</flux:table.column>
                <flux:table.column>{{ __('app.credit') }}</flux:table.column>
                <flux:table.column>{{ __('app.balance') }}</flux:table.column>
                <flux:table.column>{{ __('app.uncashed_receipts') }}</flux:table.column>
                <flux:table.column>{{ __('app.uncashed_payments') }}</flux:table.column>
                <flux:table.column>{{ __('app.final_balance') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->parties as $party)
                    @php
                        $balance = $party->debit - $party->credit;
                        $finalBalance = ($party->debit + $party->uncashed_payments) - ($party->credit + $party->uncashed_receipts);
                    @endphp
                    <flux:table.row :key="$party->PartyId">
                        <flux:table.cell>
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $party->Name }} {{ $party->LastName }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format($party->debit) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($party->credit) }}</flux:table.cell>
                        <flux:table.cell class="{{ $balance > 0 ? 'text-red-500' : ($balance < 0 ? 'text-green-500' : '') }}">
                            @if($balance < 0)
                                ({{ number_format(abs($balance)) }})
                            @else
                                {{ number_format($balance) }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format($party->uncashed_receipts) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($party->uncashed_payments) }}</flux:table.cell>
                        <flux:table.cell class="font-bold {{ $finalBalance > 0 ? 'text-red-600' : ($finalBalance < 0 ? 'text-green-600' : '') }}">
                            @if($finalBalance < 0)
                                ({{ number_format(abs($finalBalance)) }})
                            @else
                                {{ number_format($finalBalance) }}
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4">
            {{ $this->parties->links() }}
        </div>
    </flux:card>
</div>
