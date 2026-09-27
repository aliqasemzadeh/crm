<?php

use App\Livewire\Panels\Accounting\Concerns\SelectsJalaliChequeMonth;
use App\Models\Accounting\PaymentChequePeriodStat;
use App\Models\Accounting\ReceiptChequePeriodStat;
use App\Services\Accounting\ChequePeriodBuckets;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    use SelectsJalaliChequeMonth;

    #[Url]
    public int $year = 0;

    #[Url]
    public int $month = 0;

    #[Url]
    public int $periodDays = 10;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('accounting_payment_cheque_index')
            || auth()->user()?->can('accounting_receipt_cheque_index'),
            403
        );

        $this->bootJalaliChequeMonth();
        $this->periodDays = ChequePeriodBuckets::normalizeDays($this->periodDays);
    }

    public function updatedPeriodDays(int|string $value): void
    {
        $this->periodDays = ChequePeriodBuckets::normalizeDays((int) $value);
        unset($this->paymentPeriods, $this->receiptPeriods);
    }

    #[Computed]
    public function paymentPeriods(): array
    {
        return PaymentChequePeriodStat::forMonth($this->year, $this->month, $this->periodDays);
    }

    #[Computed]
    public function receiptPeriods(): array
    {
        return ReceiptChequePeriodStat::forMonth($this->year, $this->month, $this->periodDays);
    }
};
?>

<div>
    <x-slot name="title">
        {{ __('app.cheque_period_report') }}
    </x-slot>

    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.cheque_period_report') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.cheque_period_report_description') }}</flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button variant="primary" color="teal" icon="calendar-range" href="{{ route('panels.accounting.cheque-period.index', ['year' => $year, 'month' => $month, 'periodDays' => $periodDays]) }}" wire:navigate>
                    {{ __('app.cheque_period_board') }}
                </flux:button>
                @can('accounting_payment_cheque_index')
                    <flux:button variant="filled" color="zinc" icon="badge-dollar-sign" href="{{ route('panels.accounting.payment-cheque.index', ['year' => $year, 'month' => $month]) }}" wire:navigate>
                        {{ __('app.payment_cheques') }}
                    </flux:button>
                @endcan
                @can('accounting_receipt_cheque_index')
                    <flux:button variant="filled" color="zinc" icon="wallet" href="{{ route('panels.accounting.receipt-cheque.index', ['year' => $year, 'month' => $month]) }}" wire:navigate>
                        {{ __('app.receipt_cheques') }}
                    </flux:button>
                @endcan
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

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

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            @foreach(range(1, 12) as $monthNumber)
                <div
                    wire:click="selectMonth({{ $monthNumber }})"
                    wire:key="cheque-report-month-{{ $year }}-{{ $monthNumber }}"
                    class="cursor-pointer rounded-lg {{ (int) $month === (int) $monthNumber ? 'ring-2 ring-sky-500' : '' }}"
                >
                    <flux:card class="flex items-center justify-center p-4 border-t-4 {{ (int) $month === (int) $monthNumber ? 'border-sky-500' : 'border-zinc-200 dark:border-zinc-700' }}">
                        <flux:heading size="sm">{{ __('app.jalali_months.' . $monthNumber) }}</flux:heading>
                    </flux:card>
                </div>
            @endforeach
        </div>

        <flux:tabs variant="segmented" wire:model.live="periodDays">
            <flux:tab name="5">{{ __('app.cheque_period_days_5') }}</flux:tab>
            <flux:tab name="7">{{ __('app.cheque_period_days_7') }}</flux:tab>
            <flux:tab name="10">{{ __('app.cheque_period_days_10') }}</flux:tab>
        </flux:tabs>
    </div>

    @php
        $tables = [];

        if (auth()->user()?->can('accounting_payment_cheque_index')) {
            $tables[] = [
                'key' => 'payment',
                'title' => __('app.payment_cheques'),
                'border' => 'border-orange-500',
                'stats' => $this->paymentPeriods,
            ];
        }

        if (auth()->user()?->can('accounting_receipt_cheque_index')) {
            $tables[] = [
                'key' => 'receipt',
                'title' => __('app.receipt_cheques'),
                'border' => 'border-sky-500',
                'stats' => $this->receiptPeriods,
            ];
        }
    @endphp

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        @foreach($tables as $table)
            <div class="space-y-4" wire:key="cheque-report-table-{{ $table['key'] }}-{{ $year }}-{{ $month }}-{{ $periodDays }}">
                <flux:heading size="lg">
                    {{ $table['title'] }}
                    —
                    {{ __('app.jalali_months.' . $month) }}
                    {{ $year }}
                </flux:heading>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <flux:card class="border-t-4 {{ $table['border'] }}">
                        <flux:text>{{ __('app.amount') }}</flux:text>
                        <flux:heading size="lg" class="tabular-nums">
                            {{ number_format($table['stats']['total']) }}
                        </flux:heading>
                    </flux:card>
                    <flux:card class="border-t-4 border-emerald-500">
                        <flux:text>{{ __('app.cheque_passed') }}</flux:text>
                        <flux:heading size="lg" class="text-emerald-700 dark:text-emerald-400 tabular-nums">
                            {{ number_format($table['stats']['passed']) }}
                        </flux:heading>
                    </flux:card>
                    <flux:card class="border-t-4 border-rose-500">
                        <flux:text>{{ __('app.cheque_remaining') }}</flux:text>
                        <flux:heading size="lg" class="text-rose-700 dark:text-rose-400 tabular-nums">
                            {{ number_format($table['stats']['remaining']) }}
                        </flux:heading>
                    </flux:card>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('app.cheque_report_period_label') }}</flux:table.column>
                        <flux:table.column>{{ __('app.amount') }}</flux:table.column>
                        <flux:table.column>{{ __('app.cheque_passed') }}</flux:table.column>
                        <flux:table.column>{{ __('app.cheque_remaining') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($table['stats']['periods'] as $period)
                            <flux:table.row wire:key="cheque-report-row-{{ $table['key'] }}-{{ $period['from'] }}-{{ $period['to'] }}">
                                <flux:table.cell>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-medium">{{ $period['label'] }}</span>
                                        <span class="text-xs text-zinc-500">{{ $period['range'] }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="tabular-nums font-semibold">{{ number_format($period['total']) }}</flux:table.cell>
                                <flux:table.cell class="tabular-nums text-emerald-700 dark:text-emerald-400">{{ number_format($period['passed']) }}</flux:table.cell>
                                <flux:table.cell class="tabular-nums text-rose-700 dark:text-rose-400">{{ number_format($period['remaining']) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">{{ __('app.cheque_month_empty') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        @endforeach
    </div>
</div>
