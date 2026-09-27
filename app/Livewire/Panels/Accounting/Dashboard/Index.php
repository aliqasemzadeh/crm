<?php

namespace App\Livewire\Panels\Accounting\Dashboard;

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\RPA\PaymentCheque;
use App\Models\Sepidar\RPA\PaymentHeader;
use App\Models\Sepidar\RPA\ReceiptCheque;
use App\Models\Sepidar\RPA\ReceiptHeader;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    public int|string $fiscalYearRef;

    public int $selectedMonth = 0;

    public function mount(): void
    {
        $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
    }

    public function updatedFiscalYearRef(): void
    {
        unset($this->stats);
        $this->selectedMonth = 0;
    }

    public function reload(): void
    {
        self::clearCache($this->fiscalYearRef);
        unset($this->stats);
        Flux::toast(__('app.dashboard_data_reloaded'));
    }

    public function openMonthBreakdown(int $month): void
    {
        $this->authorize('accounting_profit_index');
        $this->selectedMonth = max(1, min(12, $month));
        Flux::modal('panels.accounting.dashboard.month-breakdown.modal')->show();
    }

    public static function statsCacheKey(int|string $fiscalYearRef): string
    {
        return "accounting_dashboard_stats_fiscal_year_{$fiscalYearRef}";
    }

    public static function clearCache(int|string|null $fiscalYearRef = null): void
    {
        if ($fiscalYearRef !== null) {
            Cache::forget(self::statsCacheKey($fiscalYearRef));

            return;
        }

        Cache::forget(self::statsCacheKey(config('sepidar.FiscalYearRef')));

        $ids = Cache::remember('sepidar_fiscal_year_ids', now()->addDay(), function () {
            return FiscalYear::query()->pluck('FiscalYearID')->all();
        });

        foreach ($ids as $id) {
            Cache::forget(self::statsCacheKey($id));
        }
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearID')->get();
    }

    #[Computed]
    public function monthBreakdown(): ?array
    {
        if ($this->selectedMonth < 1) {
            return null;
        }

        return $this->stats['monthBreakdown'][$this->selectedMonth] ?? null;
    }

    #[Computed]
    public function stats(): array
    {
        $fiscalYearRef = $this->fiscalYearRef;

        return Cache::rememberForever(self::statsCacheKey($fiscalYearRef), function () use ($fiscalYearRef) {
            $monthlyExpenses = array_fill(1, 12, 0.0);
            $monthlyReceipts = array_fill(1, 12, 0.0);
            $paymentCounts = array_fill(1, 12, 0);
            $receiptCounts = array_fill(1, 12, 0);
            $paymentByDescription = array_fill(1, 12, []);
            $receiptByDescription = array_fill(1, 12, []);
            $largestPayment = array_fill(1, 12, null);
            $largestReceipt = array_fill(1, 12, null);

            $payments = PaymentHeader::query()
                ->where('FiscalYearRef', $fiscalYearRef)
                ->select(['Number', 'Date', 'Description', 'TotalAmount'])
                ->get();

            foreach ($payments as $payment) {
                if (! $payment->Date) {
                    continue;
                }

                $jalaliDate = Jalalian::fromDateTime($payment->Date);
                $month = $jalaliDate->getMonth();
                $amount = (float) $payment->TotalAmount;
                $description = trim((string) $payment->Description) ?: '-';

                $monthlyExpenses[$month] += $amount;
                $paymentCounts[$month]++;
                $paymentByDescription[$month][$description] = ($paymentByDescription[$month][$description] ?? 0) + $amount;

                if ($largestPayment[$month] === null || $amount > $largestPayment[$month]['amount']) {
                    $largestPayment[$month] = [
                        'number' => $payment->Number,
                        'date' => $jalaliDate->format('%Y/%m/%d'),
                        'description' => $description,
                        'amount' => $amount,
                    ];
                }
            }

            $receipts = ReceiptHeader::query()
                ->where('FiscalYearRef', $fiscalYearRef)
                ->select(['Number', 'Date', 'Description', 'TotalAmount'])
                ->get();

            foreach ($receipts as $receipt) {
                if (! $receipt->Date) {
                    continue;
                }

                $jalaliDate = Jalalian::fromDateTime($receipt->Date);
                $month = $jalaliDate->getMonth();
                $amount = (float) $receipt->TotalAmount;
                $description = trim((string) $receipt->Description) ?: '-';

                $monthlyReceipts[$month] += $amount;
                $receiptCounts[$month]++;
                $receiptByDescription[$month][$description] = ($receiptByDescription[$month][$description] ?? 0) + $amount;

                if ($largestReceipt[$month] === null || $amount > $largestReceipt[$month]['amount']) {
                    $largestReceipt[$month] = [
                        'number' => $receipt->Number,
                        'date' => $jalaliDate->format('%Y/%m/%d'),
                        'description' => $description,
                        'amount' => $amount,
                    ];
                }
            }

            $chartData = [];
            $monthBreakdown = [];

            for ($i = 1; $i <= 12; $i++) {
                $diff = $monthlyReceipts[$i] - $monthlyExpenses[$i];

                $chartData[] = [
                    'month' => __('app.jalali_months.'.$i),
                    'monthNumber' => $i,
                    'expenses' => $monthlyExpenses[$i],
                    'receipts' => $monthlyReceipts[$i],
                    'diff' => $diff,
                ];

                $topPayment = $this->topDescriptionEntry($paymentByDescription[$i]);
                $topReceipt = $this->topDescriptionEntry($receiptByDescription[$i]);

                $monthBreakdown[$i] = [
                    'month' => __('app.jalali_months.'.$i),
                    'receipts' => $monthlyReceipts[$i],
                    'expenses' => $monthlyExpenses[$i],
                    'diff' => $diff,
                    'receiptsCount' => $receiptCounts[$i],
                    'paymentsCount' => $paymentCounts[$i],
                    'topPayment' => $topPayment,
                    'topReceipt' => $topReceipt,
                    'largestPayment' => $largestPayment[$i],
                    'largestReceipt' => $largestReceipt[$i],
                ];
            }

            [$chequeStart, $chequeEnd] = $this->jalaliYearBounds($fiscalYearRef);

            $uncashedReceiptsQuery = ReceiptCheque::query()->whereIn('State', [1, 5]);
            $uncashedPaymentsQuery = PaymentCheque::query()->whereIn('State', [1, 2]);

            if ($chequeStart && $chequeEnd) {
                $uncashedReceiptsQuery->whereBetween('Date', [$chequeStart, $chequeEnd]);
                $uncashedPaymentsQuery->whereBetween('Date', [$chequeStart, $chequeEnd]);
            }

            $totalReceipts = array_sum($monthlyReceipts);
            $totalExpenses = array_sum($monthlyExpenses);

            return [
                'monthlyExpenses' => $monthlyExpenses,
                'monthlyReceipts' => $monthlyReceipts,
                'totalExpenses' => $totalExpenses,
                'totalReceipts' => $totalReceipts,
                'receiptsPaymentsDiff' => $totalReceipts - $totalExpenses,
                'chartData' => $chartData,
                'monthBreakdown' => $monthBreakdown,
                'uncashedReceiptsSum' => (float) $uncashedReceiptsQuery->sum('Amount'),
                'uncashedPaymentsSum' => (float) $uncashedPaymentsQuery->sum('Amount'),
            ];
        });
    }

    /**
     * @param  array<string, float>  $byDescription
     * @return array{description: string, amount: float}|null
     */
    private function topDescriptionEntry(array $byDescription): ?array
    {
        if ($byDescription === []) {
            return null;
        }

        arsort($byDescription);
        $description = array_key_first($byDescription);

        return [
            'description' => $description,
            'amount' => (float) $byDescription[$description],
        ];
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function jalaliYearBounds(int|string $fiscalYearRef): array
    {
        $fiscalYear = FiscalYear::query()->find($fiscalYearRef);

        if (! $fiscalYear) {
            return [null, null];
        }

        $jalaliYear = (int) $fiscalYear->Title;

        if ($jalaliYear < 1300) {
            return [null, null];
        }

        $start = (new Jalalian($jalaliYear, 1, 1))->toCarbon()->startOfDay();
        $days = (new Jalalian($jalaliYear, 12, 1))->getMonthDays();
        $end = (new Jalalian($jalaliYear, 12, $days))->toCarbon()->endOfDay();

        return [$start, $end];
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.dashboard.index');
    }
}
