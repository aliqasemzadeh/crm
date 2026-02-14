<?php

namespace App\Livewire\Panels\Accounting\Dashboard;

use App\Models\Sepidar\RPA\PaymentHeader;
use App\Models\Sepidar\RPA\ReceiptHeader;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    #[Computed]
    public function stats()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "accounting_dashboard_stats_fiscal_year_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            // Expenses
            $payments = PaymentHeader::where('FiscalYearRef', $fiscalYearRef)
                ->select('TotalAmount', 'Date')
                ->get();

            $monthlyExpenses = array_fill(1, 12, 0);
            foreach ($payments as $payment) {
                if ($payment->Date) {
                    $jalaliDate = Jalalian::fromDateTime($payment->Date);
                    $month = $jalaliDate->getMonth();
                    $monthlyExpenses[$month] += $payment->TotalAmount;
                }
            }

            // Receipts
            $receipts = ReceiptHeader::where('FiscalYearRef', $fiscalYearRef)
                ->select('TotalAmount', 'Date')
                ->get();

            $monthlyReceipts = array_fill(1, 12, 0);
            foreach ($receipts as $receipt) {
                if ($receipt->Date) {
                    $jalaliDate = Jalalian::fromDateTime($receipt->Date);
                    $month = $jalaliDate->getMonth();
                    $monthlyReceipts[$month] += $receipt->TotalAmount;
                }
            }

            // Chart Data
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = [
                    'month' => __('app.jalali_months.' . $i),
                    'expenses' => $monthlyExpenses[$i],
                    'receipts' => $monthlyReceipts[$i],
                ];
            }

            return [
                'monthlyExpenses' => $monthlyExpenses,
                'monthlyReceipts' => $monthlyReceipts,
                'totalExpenses' => array_sum($monthlyExpenses),
                'totalReceipts' => array_sum($monthlyReceipts),
                'chartData' => $chartData,
            ];
        });
    }

    public static function clearCache()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("accounting_dashboard_stats_fiscal_year_{$fiscalYearRef}");
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.dashboard.index');
    }
}
