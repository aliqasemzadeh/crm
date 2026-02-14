<?php

namespace App\Livewire\Panels\Accounting\Invoice;

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\SLS\Invoice;
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
    public function invoiceStats()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        $cacheKey = "invoice_stats_fiscal_year_{$fiscalYearRef}";

        return Cache::rememberForever($cacheKey, function () use ($fiscalYearRef) {
            $invoices = Invoice::where('FiscalYearRef', $fiscalYearRef)
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
                'total' => array_sum($monthlyStats)
            ];
        });
    }

    public static function clearCache()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("invoice_stats_fiscal_year_{$fiscalYearRef}");
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('CustomerRealName', 'like', '%' . $this->search . '%')
                        ->orWhere('Number', 'like', '%' . $this->search . '%');
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
    public function recentInvoices()
    {
        return Invoice::orderBy('Date', 'desc')
            ->orderBy('InvoiceId', 'desc')
            ->take(50)
            ->get();
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.invoice.index');
    }
}
