<?php

namespace App\Livewire\Panels\Accounting\Invoice;

use App\Enums\InvoiceReviewStatusEnum;
use App\Models\Crm\InvoiceReview;
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

    public string $sortBy = 'CreationDate';

    public string $sortDirection = 'desc';

    public string $search = '';

    #[Url]
    public string $saleType = 'all';

    #[Url]
    public string $reviewStatus = 'all';

    public int $selectedMonth = 0;

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
        Flux::modal('panels.accounting.invoice.month-stats.modal')->show();
    }

    public function updatedSaleType(): void
    {
        unset($this->invoiceStats);
        $this->resetPage();
    }

    public function updatedReviewStatus(): void
    {
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
        $paginator = Invoice::query()
            ->with(['creator'])
            ->when($this->saleType !== 'all', function ($query) {
                if ($this->saleType === 'official') {
                    $query->where('SaleTypeRef', 1);
                } else {
                    $query->where('SaleTypeRef', '!=', 1);
                }
            })
            ->when($this->reviewStatus !== 'all', function ($query) {
                $status = InvoiceReviewStatusEnum::tryFrom($this->reviewStatus);

                if (! $status) {
                    return;
                }

                $invoiceIds = InvoiceReview::query()
                    ->where('status', $status->value)
                    ->pluck('sepidar_invoice_id');

                $query->whereIn('InvoiceId', $invoiceIds);
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
            ->paginate(20);

        $reviews = InvoiceReview::query()
            ->with('accountingReviewer')
            ->whereIn('sepidar_invoice_id', $paginator->getCollection()->pluck('InvoiceId'))
            ->get()
            ->keyBy('sepidar_invoice_id');

        $paginator->getCollection()->each(function (Invoice $invoice) use ($reviews): void {
            $invoice->setRelation('review', $reviews->get($invoice->InvoiceId));
        });

        return $paginator;
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.invoice.index');
    }
}
