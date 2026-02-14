<?php

namespace App\Livewire\Panels\Accounting\PaymentCheque;

use App\Models\Sepidar\RPA\PaymentCheque;
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
        $cacheKey = "payment_cheque_stats_fiscal_year_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $cheques = PaymentCheque::whereYear('Date', '>', 2000)
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
        Cache::forget("payment_cheque_stats_fiscal_year_{$fiscalYearRef}");
    }

    #[Computed]
    public function cheques()
    {
        return PaymentCheque::query()
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
        return PaymentCheque::with('dl')
            ->orderBy('Date', 'desc')
            ->orderBy('PaymentChequeId', 'desc')
            ->take(50)
            ->get();
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.payment-cheque.index');
    }
}
