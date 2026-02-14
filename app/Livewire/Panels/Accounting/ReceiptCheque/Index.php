<?php

namespace App\Livewire\Panels\Accounting\ReceiptCheque;

use App\Models\Sepidar\RPA\ReceiptCheque;
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
    public function chequeStats()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "receipt_cheque_stats_fiscal_year_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $cheques = ReceiptCheque::whereHas('dl', function($q) use ($fiscalYearRef) {
                // Assuming fiscal year filter might be needed, but usually cheques are across years
                // For consistency with other pages, let's filter if possible or just get all for current context
            })->whereYear('Date', '>', 2000) // basic filter
                ->select('Amount', 'Date')
                ->get();

            $monthlyStats = array_fill(1, 12, 0);

            foreach ($cheques as $cheque) {
                if ($cheque->Date) {
                    $jalaliDate = Jalalian::fromDateTime($cheque->Date);
                    $month = $jalaliDate->getMonth();
                    $monthlyStats[$month] += $cheque->Amount;
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
        Cache::forget("receipt_cheque_stats_fiscal_year_{$fiscalYearRef}");
    }

    #[Computed]
    public function cheques()
    {
        return ReceiptCheque::query()
            ->with('dl')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('Number', 'like', '%' . $this->search . '%')
                      ->orWhere('SayadCode', 'like', '%' . $this->search . '%')
                      ->orWhereHas('dl', function($inner) {
                          $inner->where('Title', 'like', '%' . $this->search . '%');
                      });
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
    public function recentCheques()
    {
        return ReceiptCheque::with('dl')
            ->orderBy('Date', 'desc')
            ->orderBy('ReceiptChequeId', 'desc')
            ->take(50)
            ->get();
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.receipt-cheque.index');
    }
}
