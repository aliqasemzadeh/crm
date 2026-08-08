<?php

namespace App\Livewire\Panels\Accounting\Invoice;

use App\Models\Sepidar\SLS\Invoice;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    use WithPagination;

    public string $sortBy = 'Date';

    public string $sortDirection = 'desc';

    public string $search = '';

    #[Url]
    public string $saleType = 'all';

    public int $selectedMonth = 0;

    public string $periodMode = 'weekly';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function showMonthDetail(int $month): void
    {
        $this->selectedMonth = $month;
        $this->periodMode = 'weekly';
        unset($this->monthPeriodStats);
        Flux::modal('panels.accounting.invoice.month-stats.modal')->show();
    }

    public function updatedPeriodMode(): void
    {
        unset($this->monthPeriodStats);
    }

    public function updatedSaleType(): void
    {
        unset($this->invoiceStats);
        unset($this->monthPeriodStats);
        $this->resetPage();
    }

    #[Computed]
    public function invoiceStats()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "invoice_stats_fiscal_year_{$fiscalYearRef}_{$this->saleType}";

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

            $monthlyStats = array_fill(1, 12, 0);

            foreach ($invoices as $invoice) {
                if ($invoice->Date) {
                    $jalaliDate = Jalalian::fromDateTime($invoice->Date);
                    $month = $jalaliDate->getMonth();
                    $monthlyStats[$month] += $invoice->Price;
                }
            }

            return [
                'monthly' => $monthlyStats,
                'total' => array_sum($monthlyStats),
            ];
        });
    }

    #[Computed]
    public function monthPeriodStats(): array
    {
        if ($this->selectedMonth < 1 || $this->selectedMonth > 12) {
            return [
                'month' => 0,
                'month_name' => '',
                'total' => 0,
                'periods' => [],
            ];
        }

        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "invoice_period_stats_{$fiscalYearRef}_{$this->saleType}_{$this->selectedMonth}_{$this->periodMode}";

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

            $monthName = __('app.jalali_months.'.$this->selectedMonth);
            $year = null;
            $dayAmounts = [];

            foreach ($invoices as $invoice) {
                if (! $invoice->Date) {
                    continue;
                }

                $jalaliDate = Jalalian::fromDateTime($invoice->Date);

                if ($jalaliDate->getMonth() !== $this->selectedMonth) {
                    continue;
                }

                $year ??= $jalaliDate->getYear();
                $day = $jalaliDate->getDay();
                $dayAmounts[$day] = ($dayAmounts[$day] ?? 0) + $invoice->Price;
            }

            $year ??= (int) Jalalian::now()->getYear();
            $daysInMonth = (new Jalalian($year, $this->selectedMonth, 1))->getMonthDays();
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
                'month' => $this->selectedMonth,
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

    public static function clearCache()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        foreach (['all', 'official', 'unofficial'] as $saleType) {
            Cache::forget("invoice_stats_fiscal_year_{$fiscalYearRef}_{$saleType}");

            foreach (range(1, 12) as $month) {
                foreach (['weekly', 'ten_days'] as $mode) {
                    Cache::forget("invoice_period_stats_{$fiscalYearRef}_{$saleType}_{$month}_{$mode}");
                }
            }
        }
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->with(['creator'])
            ->when($this->saleType !== 'all', function ($query) {
                if ($this->saleType === 'official') {
                    $query->where('SaleTypeRef', 1);
                } else {
                    $query->where('SaleTypeRef', '!=', 1);
                }
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('CustomerRealName', 'like', '%'.$this->search.'%')
                        ->orWhere('Number', 'like', '%'.$this->search.'%');
                });
            })
            ->tap(function ($query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(300);
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.invoice.index');
    }
}
