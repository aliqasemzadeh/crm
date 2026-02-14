<?php

namespace App\Livewire\Panels\Accounting\InventoryReceipt;

use App\Models\Sepidar\INV\InventoryReceipt;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    use WithPagination;

    public string $sortBy = 'Date';
    public string $sortDirection = 'desc';
    public string $search = '';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function receiptStats()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "inventory_receipt_stats_fiscal_year_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $receipts = InventoryReceipt::where('FiscalYearRef', $fiscalYearRef)
                ->select('Price', 'Date')
                ->get();

            $monthlyStats = array_fill(1, 12, 0);

            foreach ($receipts as $receipt) {
                if ($receipt->Date) {
                    $jalaliDate = Jalalian::fromDateTime($receipt->Date);
                    $month = $jalaliDate->getMonth();
                    $monthlyStats[$month] += $receipt->Price;
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
        Cache::forget("inventory_receipt_stats_fiscal_year_{$fiscalYearRef}");
    }

    #[Computed]
    public function receipts()
    {
        return InventoryReceipt::query()
            ->with('dl')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('Number', 'like', '%' . $this->search . '%');
                });
            })
            ->tap(function ($query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(100);
    }

    #[Computed]
    public function recentReceipts()
    {
        return InventoryReceipt::with('dl')
            ->orderBy('Date', 'desc')
            ->orderBy('InventoryReceiptID', 'desc')
            ->take(50)
            ->get();
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.inventory-receipt.index');
    }
}
