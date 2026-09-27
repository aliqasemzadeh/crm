<div class="space-y-6 mb-10">
    <div class="flex items-center justify-between gap-3">
        <flux:button type="button" variant="filled" color="zinc" icon="chevron-right" wire:click="previousYear">
            {{ __('app.previous_year') }}
        </flux:button>
        <flux:heading size="lg">{{ $year }}</flux:heading>
        <flux:button type="button" variant="filled" color="zinc" icon="chevron-left" wire:click="nextYear">
            {{ __('app.next_year') }}
        </flux:button>
    </div>

    <flux:text>{{ __('app.cheque_click_month') }}</flux:text>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($this->chequeStats['monthly'] as $monthNumber => $monthStats)
            @php
                $monthRemaining = $monthStats['total'] - $monthStats['passed'];
            @endphp
            <div
                wire:click="selectMonth({{ $monthNumber }})"
                wire:key="cheque-month-{{ $year }}-{{ $monthNumber }}"
                class="cursor-pointer rounded-lg {{ (int) $month === (int) $monthNumber ? 'ring-2 ring-sky-500' : '' }}"
            >
                <flux:card class="flex flex-col items-center justify-center p-6 border-t-4 {{ (int) $month === (int) $monthNumber ? 'border-sky-500' : 'border-zinc-200 dark:border-zinc-700' }}">
                    <flux:heading size="lg" class="mb-2">
                        {{ __('app.jalali_months.' . $monthNumber) }}
                    </flux:heading>
                    <flux:text size="xl" class="font-bold text-zinc-800 dark:text-zinc-100 tabular-nums">
                        <span class="text-emerald-600 dark:text-emerald-400">{{ number_format($monthStats['passed']) }}</span>
                        <span class="text-zinc-400 dark:text-zinc-500">/</span>
                        <span>{{ number_format($monthStats['total']) }}</span>
                    </flux:text>
                    <flux:text class="mt-1 text-xs text-zinc-500">{{ __('app.cheque_passed_of_total') }}</flux:text>
                    <flux:text class="mt-2 text-sm font-semibold text-rose-600 dark:text-rose-400 tabular-nums">
                        {{ __('app.cheque_remaining') }}:
                        {{ number_format($monthRemaining) }}
                    </flux:text>
                </flux:card>
            </div>
        @endforeach
    </div>

    <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-zinc-500">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <flux:heading size="lg">{{ $yearTotalLabel }}</flux:heading>
            <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-end">
                <flux:text size="lg" class="font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                    {{ __('app.cheque_passed') }}:
                    {{ number_format($this->chequeStats['passed']) }}
                </flux:text>
                <flux:text size="lg" class="font-bold tabular-nums text-rose-600 dark:text-rose-400">
                    {{ __('app.cheque_remaining') }}:
                    {{ number_format($this->chequeStats['remaining'] ?? ($this->chequeStats['total'] - $this->chequeStats['passed'])) }}
                </flux:text>
                <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white tabular-nums">
                    {{ number_format($this->chequeStats['total']) }}
                    <span class="text-lg font-bold">{{ __('app.rial') }}</span>
                </flux:text>
            </div>
        </div>
    </flux:card>
</div>

<div class="space-y-4 mb-10">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">
            {{ __('app.cheque_month_report') }}
            {{ __('app.jalali_months.' . $month) }}
            {{ $year }}
        </flux:heading>
        <flux:button
            variant="primary"
            color="teal"
            icon="calendar-range"
            icon:variant="outline"
            href="{{ route('panels.accounting.cheque-period.index', ['year' => $year, 'month' => $month]) }}"
            wire:navigate
        >
            {{ __('app.cheque_period_board') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 {{ $this->showsDepositNeeded() ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }} gap-4">
        <flux:card class="border-t-4 border-emerald-500">
            <flux:text>{{ __('app.cheque_passed') }}</flux:text>
            <flux:heading size="xl" class="text-emerald-700 dark:text-emerald-400 tabular-nums">
                {{ number_format($this->monthReport['passed']) }} {{ __('app.rial') }}
            </flux:heading>
        </flux:card>
        <flux:card class="border-t-4 border-rose-500">
            <flux:text>{{ __('app.cheque_remaining') }}</flux:text>
            <flux:heading size="xl" class="text-rose-700 dark:text-rose-400 tabular-nums">
                {{ number_format($this->monthReport['total'] - $this->monthReport['passed']) }} {{ __('app.rial') }}
            </flux:heading>
        </flux:card>
        <flux:card class="border-t-4 border-zinc-500">
            <flux:text>{{ __('app.amount') }}</flux:text>
            <flux:heading size="xl" class="tabular-nums">
                {{ number_format($this->monthReport['total']) }} {{ __('app.rial') }}
            </flux:heading>
        </flux:card>
        @if($this->showsDepositNeeded())
            @php $deposit = $this->monthReport['deposit']; @endphp
            <flux:card class="border-t-4 {{ $deposit > 0 ? 'border-orange-500' : 'border-emerald-500' }}">
                <flux:text>{{ __('app.cheque_deposit_needed') }}</flux:text>
                <flux:heading size="xl" class="tabular-nums {{ $deposit > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                    {{ number_format($deposit) }} {{ __('app.rial') }}
                </flux:heading>
            </flux:card>
        @endif
    </div>

    @if($this->showsDepositNeeded())
        <flux:text class="text-sm text-zinc-500">{{ __('app.cheque_deposit_needed_hint') }}</flux:text>
    @endif

    @if(count($this->monthReport['accounts']) > 0)
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.cheque_bank_account') }}</flux:table.column>
                <flux:table.column>{{ __('app.cheque_passed') }}</flux:table.column>
                <flux:table.column>{{ __('app.cheque_remaining') }}</flux:table.column>
                <flux:table.column>{{ __('app.cheque_account_balance') }}</flux:table.column>
                @if($this->showsDepositNeeded())
                    <flux:table.column>{{ __('app.cheque_deposit_needed') }}</flux:table.column>
                @endif
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->monthReport['accounts'] as $account)
                    <flux:table.row wire:key="cheque-account-{{ $month }}-{{ $loop->index }}">
                        <flux:table.cell>{{ $account['label'] }}</flux:table.cell>
                        <flux:table.cell class="text-emerald-700 dark:text-emerald-400 tabular-nums">{{ number_format($account['passed']) }}</flux:table.cell>
                        <flux:table.cell class="text-rose-700 dark:text-rose-400 tabular-nums">{{ number_format($account['unpassed']) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ $account['balance'] === null ? '—' : number_format($account['balance']) }}
                            {{ __('app.rial') }}
                        </flux:table.cell>
                        @if($this->showsDepositNeeded())
                            <flux:table.cell class="font-semibold tabular-nums {{ ($account['deposit'] ?? 0) > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                                {{ number_format($account['deposit'] ?? 0) }} {{ __('app.rial') }}
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:text>{{ __('app.cheque_month_empty') }}</flux:text>
    @endif
</div>
