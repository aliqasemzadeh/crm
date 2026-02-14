<?php

namespace App\Livewire\Panels\Accounting\ReceiptHeader;

use App\Models\Sepidar\RPA\ReceiptHeader;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    #[Computed]
    public function receiptStats()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "receipt_stats_fiscal_year_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $receipts = ReceiptHeader::where('FiscalYearRef', $fiscalYearRef)
                ->select('TotalAmount', 'Date')
                ->get();

            $monthlyStats = array_fill(1, 12, 0);

            foreach ($receipts as $receipt) {
                if ($receipt->Date) {
                    $jalaliDate = Jalalian::fromDateTime($receipt->Date);
                    $month = $jalaliDate->getMonth();
                    $monthlyStats[$month] += $receipt->TotalAmount;
                }
            }

            return [
                'monthly' => $monthlyStats,
                'total' => array_sum($monthlyStats)
            ];
        });
    }

    public static function clearCache()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("receipt_stats_fiscal_year_{$fiscalYearRef}");
    }

    #[Computed]
    public function recentReceipts()
    {
        return ReceiptHeader::orderBy('Date', 'desc')
            ->orderBy('ReceiptHeaderId', 'desc')
            ->take(50)
            ->get();
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.receipt-header.index');
    }
}
