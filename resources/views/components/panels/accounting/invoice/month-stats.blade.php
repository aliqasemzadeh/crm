<?php

use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new class extends Component
{
    public int $month = 0;

    public string $saleType = 'all';

    public string $periodMode = 'weekly';

    public function updatedPeriodMode(): void
    {
        unset($this->monthPeriodStats);
    }

    #[Computed]
    public function monthPeriodStats(): array
    {
        if ($this->month < 1 || $this->month > 12) {
            return [
                'month' => 0,
                'month_name' => '',
                'total' => 0,
                'periods' => [],
            ];
        }

        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "invoice_period_stats_{$fiscalYearRef}_{$this->saleType}_{$this->month}_{$this->periodMode}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $invoices = Invoice::where('FiscalYearRef', $fiscalYearRef)
                ->when($this->saleType !== 'all', function ($query) {
                    if ($this->saleType === 'official') {
                        $query->where('SaleTypeRef', 1);
                    } else {
                        $query->where('SaleTypeRef', '!=', 1);
                    }
                })
                ->select('Price', 'Date')
                ->get();

            $monthName = __('app.jalali_months.'.$this->month);
            $year = null;
            $dayAmounts = [];

            foreach ($invoices as $invoice) {
                if (! $invoice->Date) {
                    continue;
                }

                $jalaliDate = Jalalian::fromDateTime($invoice->Date);

                if ($jalaliDate->getMonth() !== $this->month) {
                    continue;
                }

                $year ??= $jalaliDate->getYear();
                $day = $jalaliDate->getDay();
                $dayAmounts[$day] = ($dayAmounts[$day] ?? 0) + $invoice->Price;
            }

            $year ??= (int) Jalalian::now()->getYear();
            $daysInMonth = (new Jalalian($year, $this->month, 1))->getMonthDays();
            $ranges = $this->buildPeriodRanges($daysInMonth, $this->periodMode);
            $total = array_sum($dayAmounts);
            $periods = [];

            foreach ($ranges as $index => [$from, $to]) {
                $amount = 0;

                for ($day = $from; $day <= $to; $day++) {
                    $amount += $dayAmounts[$day] ?? 0;
                }

                $ordinal = __('app.ordinals.'.($index + 1));
                $labelKey = $this->periodMode === 'ten_days'
                    ? 'app.invoice_period_ten_days_label'
                    : 'app.invoice_period_week_label';

                $periods[] = [
                    'label' => __($labelKey, ['ordinal' => $ordinal, 'month' => $monthName]),
                    'from' => $from,
                    'to' => $to,
                    'range' => __('app.invoice_period_range', ['from' => $from, 'to' => $to]),
                    'amount' => $amount,
                    'percent' => $total > 0 ? round(($amount / $total) * 100, 1) : 0,
                ];
            }

            return [
                'month' => $this->month,
                'month_name' => $monthName,
                'total' => $total,
                'periods' => $periods,
            ];
        });
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    protected function buildPeriodRanges(int $daysInMonth, string $mode): array
    {
        if ($mode === 'ten_days') {
            $ranges = [
                [1, min(10, $daysInMonth)],
            ];

            if ($daysInMonth > 10) {
                $ranges[] = [11, min(20, $daysInMonth)];
            }

            if ($daysInMonth > 20) {
                $ranges[] = [21, $daysInMonth];
            }

            return $ranges;
        }

        $ranges = [];

        for ($start = 1; $start <= $daysInMonth; $start += 7) {
            $ranges[] = [$start, min($start + 6, $daysInMonth)];
        }

        return $ranges;
    }

    public function placeholder()
    {
        $message = e(__('app.invoice_period_calculating'));

        return <<<HTML
        <div class="flex flex-col items-center justify-center gap-3 py-12">
            <svg class="animate-spin size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm text-zinc-500">{$message}</p>
        </div>
        HTML;
    }
};
?>

<div class="space-y-6">
    @php
        $monthStats = $this->monthPeriodStats;
    @endphp

    <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-zinc-500">
        <div class="flex justify-between items-center gap-4">
            <flux:heading size="sm">{{ __('app.invoice_month_total') }}</flux:heading>
            <flux:text size="lg" class="font-bold text-zinc-900 dark:text-white">
                {{ number_format($monthStats['total']) }}
                <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
            </flux:text>
        </div>
    </flux:card>

    <flux:tabs variant="segmented" wire:model.live="periodMode">
        <flux:tab name="weekly">{{ __('app.invoice_period_weekly') }}</flux:tab>
        <flux:tab name="ten_days">{{ __('app.invoice_period_ten_days') }}</flux:tab>
    </flux:tabs>

    <div wire:loading.delay wire:target="periodMode" class="flex flex-col items-center justify-center gap-3 py-8">
        <flux:icon.loader-circle class="animate-spin text-zinc-400" />
        <flux:text size="sm" class="text-zinc-500">{{ __('app.invoice_period_calculating') }}</flux:text>
    </div>

    <div wire:loading.remove.delay wire:target="periodMode" class="space-y-3">
        @forelse($monthStats['periods'] as $period)
            <flux:card class="border-t-4 border-sky-500">
                <div class="flex flex-col gap-2">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <flux:heading size="sm">{{ $period['label'] }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-500">{{ $period['range'] }}</flux:text>
                        </div>
                        <flux:badge color="sky" size="sm">{{ $period['percent'] }}%</flux:badge>
                    </div>
                    <flux:text size="lg" class="font-bold text-zinc-800 dark:text-zinc-100">
                        {{ number_format($period['amount']) }}
                        <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:text>
                </div>
            </flux:card>
        @empty
            <flux:text>{{ __('app.invoice_period_no_data') }}</flux:text>
        @endforelse
    </div>
</div>
