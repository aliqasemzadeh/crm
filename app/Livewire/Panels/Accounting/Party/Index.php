<?php

namespace App\Livewire\Panels\Accounting\Party;

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\GNR\Party;
use App\Support\Accounting\CustomerSegment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    use WithPagination;

    public string $sortBy = 'CreationDate';

    public string $sortDirection = 'desc';

    public string $search = '';

    public string $customerSegment = CustomerSegment::ALL;

    public int|string $fiscalYearRef = 0;

    public int $selectedMonth = 0;

    public function mount(): void
    {
        $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $this->selectedMonth = 0;
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCustomerSegment(): void
    {
        if (! array_key_exists($this->customerSegment, CustomerSegment::options())) {
            $this->customerSegment = CustomerSegment::ALL;
        }

        if (! CustomerSegment::isValuable($this->customerSegment)) {
            $this->sortBy = 'CreationDate';
            $this->sortDirection = 'desc';
        } else {
            $this->sortBy = 'total_profit';
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
        unset($this->customers);
    }

    public function updatedFiscalYearRef(): void
    {
        $this->fiscalYearRef = (int) $this->fiscalYearRef;
        $this->resetPage();
        unset($this->customers);
    }

    public function updatedSelectedMonth(): void
    {
        $this->selectedMonth = max(0, min(12, (int) $this->selectedMonth));
        $this->resetPage();
        unset($this->customers);
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearID')->get();
    }

    #[Computed]
    public function customers()
    {
        $query = Party::query()
            ->from('GNR.Party as p')
            ->where('p.IsCustomer', 1)
            ->when($this->search !== '', function ($query) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('p.Name', 'like', '%'.$search.'%')
                        ->orWhere('p.LastName', 'like', '%'.$search.'%')
                        ->orWhere('p.EconomicCode', 'like', '%'.$search.'%')
                        ->orWhere('p.IdentificationCode', 'like', '%'.$search.'%');
                });
            });

        $groupingIds = CustomerSegment::groupingIdsFor($this->customerSegment);

        if ($groupingIds !== null) {
            $query->whereIn('p.CustomerGroupingRef', $groupingIds);
        }

        if (CustomerSegment::isValuable($this->customerSegment)) {
            [$start, $end] = $this->periodBounds();

            if (! $start || ! $end) {
                return Party::query()->whereRaw('1 = 0')->paginate(100);
            }

            $latestFeeIds = DB::connection('sqlsrv')
                ->table('INV.InventoryReceiptItem')
                ->selectRaw('MAX(InventoryReceiptItemID) as InventoryReceiptItemID')
                ->groupBy('ItemRef');

            $latestFees = DB::connection('sqlsrv')
                ->table('INV.InventoryReceiptItem as iri')
                ->joinSub($latestFeeIds, 'latest', 'latest.InventoryReceiptItemID', '=', 'iri.InventoryReceiptItemID')
                ->select(['iri.ItemRef', 'iri.Fee']);

            $profitAgg = DB::connection('sqlsrv')
                ->table('SLS.InvoiceItem as ii')
                ->join('SLS.Invoice as i', 'ii.InvoiceRef', '=', 'i.InvoiceId')
                ->leftJoinSub($latestFees, 'fee', 'fee.ItemRef', '=', 'ii.ItemRef')
                ->where('i.State', '<>', 2)
                ->where('i.FiscalYearRef', $this->fiscalYearRef)
                ->whereBetween('i.Date', [$start, $end])
                ->where('ii.Quantity', '>', 0)
                ->groupBy('i.CustomerPartyRef')
                ->selectRaw('i.CustomerPartyRef, COUNT(DISTINCT i.InvoiceId) as invoice_count, SUM(ii.NetPrice - ISNULL(fee.Fee, 0) * ii.Quantity) as total_profit');

            $query
                ->joinSub($profitAgg, 'agg', 'agg.CustomerPartyRef', '=', 'p.PartyId')
                ->select([
                    'p.PartyId',
                    'p.Name',
                    'p.LastName',
                    'p.EconomicCode',
                    'p.IdentificationCode',
                    'p.CreationDate',
                    'agg.invoice_count',
                    'agg.total_profit',
                ]);

            $sortColumn = match ($this->sortBy) {
                'LastName' => 'p.LastName',
                'total_profit' => 'agg.total_profit',
                default => 'agg.total_profit',
            };

            return $query
                ->orderBy($sortColumn, $this->sortDirection)
                ->paginate(100);
        }

        return $query
            ->select([
                'p.PartyId',
                'p.Name',
                'p.LastName',
                'p.EconomicCode',
                'p.IdentificationCode',
                'p.CreationDate',
            ])
            ->tap(function ($query) {
                if ($this->sortBy && $this->sortBy !== 'total_profit') {
                    $query->orderBy('p.'.$this->sortBy, $this->sortDirection);
                } else {
                    $query->orderBy('p.CreationDate', 'desc');
                }
            })
            ->paginate(100);
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function periodBounds(): array
    {
        $fiscalYear = FiscalYear::query()->find($this->fiscalYearRef);

        if (! $fiscalYear) {
            return [null, null];
        }

        $month = (int) $this->selectedMonth;

        if ($month === 0) {
            $start = $fiscalYear->StartDate
                ? Carbon::parse($fiscalYear->StartDate)->startOfDay()
                : null;
            $end = $fiscalYear->EndDate
                ? Carbon::parse($fiscalYear->EndDate)->endOfDay()
                : null;

            return [$start, $end];
        }

        $jalaliYear = (int) $fiscalYear->Title;

        if ($jalaliYear < 1300 || $month < 1 || $month > 12) {
            return [null, null];
        }

        $start = (new Jalalian($jalaliYear, $month, 1))->toCarbon()->startOfDay();
        $days = (new Jalalian($jalaliYear, $month, 1))->getMonthDays();
        $end = (new Jalalian($jalaliYear, $month, $days))->toCarbon()->endOfDay();

        if ($fiscalYear->StartDate) {
            $fyStart = Carbon::parse($fiscalYear->StartDate)->startOfDay();
            if ($start->lt($fyStart)) {
                $start = $fyStart;
            }
        }

        if ($fiscalYear->EndDate) {
            $fyEnd = Carbon::parse($fiscalYear->EndDate)->endOfDay();
            if ($end->gt($fyEnd)) {
                $end = $fyEnd;
            }
        }

        if ($start->gt($end)) {
            return [null, null];
        }

        return [$start, $end];
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.party.index');
    }
}
