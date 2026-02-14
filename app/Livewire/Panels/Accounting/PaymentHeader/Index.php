<?php

namespace App\Livewire\Panels\Accounting\PaymentHeader;

use App\Models\Sepidar\RPA\PaymentHeader;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    public function getExpensesProperty()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "expenses_fiscal_year_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $payments = PaymentHeader::where('FiscalYearRef', $fiscalYearRef)
                ->select('TotalAmount', 'Date')
                ->get();

            $monthlyExpenses = array_fill(1, 12, 0);

            foreach ($payments as $payment) {
                // Assuming Date is a string or Carbon instance.
                // Sepidar dates are often stored as strings like '2023-03-21 00:00:00' or similar.
                $jalaliDate = Jalalian::fromDateTime($payment->Date);
                $month = $jalaliDate->getMonth();
                $monthlyExpenses[$month] += $payment->TotalAmount;
            }

            return [
                'monthly' => $monthlyExpenses,
                'total' => array_sum($monthlyExpenses)
            ];
        });
    }

    public static function clearCache()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("expenses_fiscal_year_{$fiscalYearRef}");
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.payment-header.index', [
            'expenses' => $this->expenses
        ]);
    }
}
