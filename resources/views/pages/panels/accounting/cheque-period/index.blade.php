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

    public function selectMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            return;
        }

        $this->month = $month;
        unset($this->paymentPeriods, $this->receiptPeriods);
        $this->resetChequeListPage();
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
        {{ __('app.cheque_period_board') }}
    </x-slot>

    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.cheque_period_board') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.cheque_period_board_description') }}</flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
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

    <div class="space-y-6 mb-8">
        <div class="flex items-center justify-between gap-3">
            <flux:button type="button" variant="filled" color="zinc" icon="chevron-right" wire:click="previousYear">
                {{ __('app.previous_year') }}
            </flux:button>
            <flux:heading size="lg">{{ $year }}</flux:heading>
            <flux:button type="button" variant="filled" color="zinc" icon="chevron-left" wire:click="nextYear">
                {{ __('app.next_year') }}
            </flux:button>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 xl:grid-cols-12 gap-2">
            @foreach(range(1, 12) as $monthNumber)
                <div
                    wire:click="selectMonth({{ $monthNumber }})"
                    wire:key="cheque-period-month-{{ $year }}-{{ $monthNumber }}"
                    class="cursor-pointer rounded-lg {{ (int) $month === (int) $monthNumber ? 'ring-2 ring-sky-500' : '' }}"
                >
                    <flux:card class="flex items-center justify-center px-2 py-3 border-t-4 {{ (int) $month === (int) $monthNumber ? 'border-sky-500' : 'border-zinc-200 dark:border-zinc-700' }}">
                        <flux:text size="sm" class="font-medium text-center">{{ __('app.jalali_months.' . $monthNumber) }}</flux:text>
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
        $sections = [];

        if (auth()->user()?->can('accounting_payment_cheque_index')) {
            $sections[] = [
                'key' => 'payment',
                'title' => __('app.payment_cheques'),
                'border' => 'border-orange-500',
                'stats' => $this->paymentPeriods,
            ];
        }

        if (auth()->user()?->can('accounting_receipt_cheque_index')) {
            $sections[] = [
                'key' => 'receipt',
                'title' => __('app.receipt_cheques'),
                'border' => 'border-sky-500',
                'stats' => $this->receiptPeriods,
            ];
        }
    @endphp

    <div class="space-y-10">
        @foreach($sections as $section)
            <div class="space-y-4" wire:key="cheque-period-section-{{ $section['key'] }}-{{ $year }}-{{ $month }}-{{ $periodDays }}">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <flux:heading size="lg">
                        {{ $section['title'] }}
                        —
                        {{ __('app.jalali_months.' . $month) }}
                        {{ $year }}
                    </flux:heading>
                    <div class="flex flex-wrap gap-4 text-sm tabular-nums">
                        <span>{{ __('app.amount') }}: <strong>{{ number_format($section['stats']['total']) }}</strong></span>
                        <span class="text-emerald-600 dark:text-emerald-400">{{ __('app.cheque_passed') }}: <strong>{{ number_format($section['stats']['passed']) }}</strong></span>
                        <span class="text-rose-600 dark:text-rose-400">{{ __('app.cheque_remaining') }}: <strong>{{ number_format($section['stats']['remaining']) }}</strong></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @forelse($section['stats']['periods'] as $period)
                        <flux:card
                            wire:key="cheque-period-card-{{ $section['key'] }}-{{ $period['from'] }}-{{ $period['to'] }}"
                            class="border-t-4 {{ $section['border'] }} {{ $period['total'] > 0 ? '' : 'opacity-60' }}"
                        >
                            <div class="flex flex-col gap-3">
                                <div>
                                    <flux:heading size="sm">{{ $period['label'] }}</flux:heading>
                                    <flux:text size="sm" class="text-zinc-500">{{ $period['range'] }}</flux:text>
                                </div>

                                <div class="space-y-2 tabular-nums">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <flux:text size="sm">{{ __('app.amount') }}</flux:text>
                                        <flux:heading size="lg" class="text-zinc-900 dark:text-white">
                                            {{ number_format($period['total']) }}
                                        </flux:heading>
                                    </div>
                                    <div class="flex items-baseline justify-between gap-2">
                                        <flux:text size="sm" class="text-emerald-600 dark:text-emerald-400">{{ __('app.cheque_passed') }}</flux:text>
                                        <flux:text class="font-semibold text-emerald-700 dark:text-emerald-400">
                                            {{ number_format($period['passed']) }}
                                        </flux:text>
                                    </div>
                                    <div class="flex items-baseline justify-between gap-2">
                                        <flux:text size="sm" class="text-rose-600 dark:text-rose-400">{{ __('app.cheque_remaining') }}</flux:text>
                                        <flux:text class="font-semibold text-rose-700 dark:text-rose-400">
                                            {{ number_format($period['remaining']) }}
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:card>
                    @empty
                        <flux:text>{{ __('app.cheque_month_empty') }}</flux:text>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
