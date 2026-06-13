<?php

namespace App\Livewire\Panels\Accounting\Invoice;

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\SLS\Invoice;
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
                'total' => array_sum($monthlyStats)
            ];
        });
    }

    public static function clearCache()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("invoice_stats_fiscal_year_{$fiscalYearRef}_all");
        Cache::forget("invoice_stats_fiscal_year_{$fiscalYearRef}_official");
        Cache::forget("invoice_stats_fiscal_year_{$fiscalYearRef}_unofficial");
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
                    $q->where('CustomerRealName', 'like', '%' . $this->search . '%')
                        ->orWhere('Number', 'like', '%' . $this->search . '%');
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
