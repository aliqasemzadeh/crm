<?php

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\FMK\User as SepidarUser;
use App\Models\Sepidar\SLS\Invoice;
use App\Services\Accounting\SalesExpertPerformanceService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    use WithPagination;

    public SepidarUser $sepidarUser;

    public string $search = '';

    #[Url]
    public int|string $fiscalYearRef = '';

    #[Url]
    public int $selectedMonth = 0;

    #[Url]
    public string $saleType = 'all';

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public string $sortBy = 'Date';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'fiscalYearRef' => ['except' => ''],
        'selectedMonth' => ['except' => 0],
        'saleType' => ['except' => 'all'],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
    ];

    public function mount(SepidarUser $sepidarUser): void
    {
        $this->authorize('accounting_user_report');

        abort_if($sepidarUser->IsDeleted, 404);

        $this->sepidarUser = $sepidarUser;

        if ($this->fiscalYearRef === '' || $this->fiscalYearRef === null) {
            $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        }
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function selectMonth(int $month): void
    {
        $this->selectedMonth = $this->selectedMonth === $month ? 0 : $month;
        $this->resetPage();
        unset($this->invoiceStats, $this->selectedMonthInvoiceIds, $this->invoices, $this->performanceMetrics);
    }

    public function updatedFiscalYearRef(): void
    {
        $this->selectedMonth = 0;
        $this->resetPage();
        unset(
            $this->invoiceStats,
            $this->selectedMonthInvoiceIds,
            $this->invoices,
            $this->performanceMetrics,
            $this->monthlyTrend,
            $this->fiscalYears
        );
    }

    public function updatedSelectedMonth(): void
    {
        $this->selectedMonth = max(0, min(12, (int) $this->selectedMonth));
        $this->resetPage();
        unset($this->invoiceStats, $this->selectedMonthInvoiceIds, $this->invoices, $this->performanceMetrics);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedMonth = 0;
        $this->saleType = 'all';
        $this->startDate = '';
        $this->endDate = '';
        $this->resetPage();
        unset($this->invoiceStats, $this->selectedMonthInvoiceIds, $this->invoices, $this->performanceMetrics);
    }

    protected function monthFilter(): ?int
    {
        return $this->selectedMonth > 0 ? $this->selectedMonth : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSaleType(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->resetPage();
    }

    public function gregorianDate(?string $persianDate): ?string
    {
        if (! $persianDate) {
            return null;
        }

        try {
            return Jalalian::fromFormat('Y/m/d', $persianDate)->toCarbon()->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    protected function baseInvoiceQuery(): Builder
    {
        $fiscalYearRef = (int) $this->fiscalYearRef;
        [$yearStart, $yearEnd] = app(SalesExpertPerformanceService::class)
            ->periodBounds($fiscalYearRef, null);

        return Invoice::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Creator', $this->sepidarUser->UserID)
            ->where('State', '<>', 2)
            ->when($yearStart && $yearEnd && $this->startDate === '' && $this->endDate === '', function (Builder $query) use ($yearStart, $yearEnd) {
                $query->whereBetween('Date', [$yearStart, $yearEnd]);
            })
            ->when($this->saleType !== 'all', function (Builder $query) {
                if ($this->saleType === 'official') {
                    $query->where('SaleTypeRef', 1);
                } else {
                    $query->where('SaleTypeRef', '!=', 1);
                }
            })
            ->when($this->startDate !== '', function (Builder $query) {
                $startDate = $this->gregorianDate($this->startDate);
                if ($startDate) {
                    $query->where('Date', '>=', $startDate);
                }
            })
            ->when($this->endDate !== '', function (Builder $query) {
                $endDate = $this->gregorianDate($this->endDate);
                if ($endDate) {
                    $query->where('Date', '<=', $endDate);
                }
            });
    }

    #[Computed]
    public function selectedMonthInvoiceIds(): array
    {
        if ($this->selectedMonth <= 0) {
            return [];
        }

        return $this->baseInvoiceQuery()
            ->whereNotNull('Date')
            ->get(['InvoiceId', 'Date'])
            ->filter(function (Invoice $invoice) {
                return Jalalian::fromDateTime($invoice->Date)->getMonth() === $this->selectedMonth;
            })
            ->pluck('InvoiceId')
            ->all();
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearID')->get();
    }

    #[Computed]
    public function performanceMetrics(): array
    {
        $metrics = app(SalesExpertPerformanceService::class)->metricsForCreators(
            [(int) $this->sepidarUser->UserID],
            (int) $this->fiscalYearRef,
            $this->monthFilter(),
            auth()->user()?->can('accounting_profit_index') ?? false
        );

        return $metrics[(int) $this->sepidarUser->UserID] ?? [
            'customer_count' => 0,
            'invoice_count' => 0,
            'sold_quantity' => 0,
            'new_customer_count' => 0,
            'profit' => null,
            'proforma_count' => null,
            'lead_conversion_rate' => null,
            'buyer_type_frequency' => null,
        ];
    }

    #[Computed]
    public function monthlyTrend(): array
    {
        return app(SalesExpertPerformanceService::class)->monthlyTrend(
            (int) $this->sepidarUser->UserID,
            (int) $this->fiscalYearRef
        );
    }

    #[Computed]
    public function invoiceStats(): array
    {
        $invoices = $this->baseInvoiceQuery()
            ->select(['Price', 'Date'])
            ->whereNotNull('Date')
            ->get();

        $monthlyStats = array_fill(1, 12, 0);

        foreach ($invoices as $invoice) {
            $month = Jalalian::fromDateTime($invoice->Date)->getMonth();
            $monthlyStats[$month] += (float) $invoice->Price;
        }

        return [
            'monthly' => $monthlyStats,
            'total' => array_sum($monthlyStats),
            'count' => $invoices->count(),
        ];
    }

    #[Computed]
    public function invoices()
    {
        $monthInvoiceIds = $this->selectedMonthInvoiceIds;

        return $this->baseInvoiceQuery()
            ->with(['customer'])
            ->when($this->selectedMonth > 0, function (Builder $query) use ($monthInvoiceIds) {
                $query->whereIn('InvoiceId', $monthInvoiceIds ?: [0]);
            })
            ->when($this->search !== '', function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('CustomerRealName', 'like', $search)
                        ->orWhere('Number', 'like', $search);
                });
            })
            ->tap(function (Builder $query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(100);
    }
};
?>

<x-slot name="title">
    {{ __('app.user_invoices_report') }} - {{ $sepidarUser->Name }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.user_invoices_report') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">
                    {{ $sepidarUser->Name }}
                    @if($sepidarUser->UserName)
                        <span class="text-zinc-500">({{ $sepidarUser->UserName }})</span>
                    @endif
                </flux:subheading>
                <flux:text class="text-zinc-500">{{ __('app.user_invoices_report_description') }}</flux:text>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
                <flux:select wire:model.live="selectedMonth" class="w-40">
                    <flux:select.option value="0">{{ __('app.whole_year') }}</flux:select.option>
                    @for($i = 1; $i <= 12; $i++)
                        <flux:select.option value="{{ $i }}">{{ __('app.jalali_months.'.$i) }}</flux:select.option>
                    @endfor
                </flux:select>

                <flux:select wire:model.live="fiscalYearRef" class="w-48">
                    @foreach($this->fiscalYears as $year)
                        <flux:select.option value="{{ $year->FiscalYearID }}">{{ $year->Title }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:tabs variant="segmented" wire:model.live="saleType">
                    <flux:tab name="all">{{ __('app.all') }}</flux:tab>
                    <flux:tab name="official">{{ __('app.official') }}</flux:tab>
                    <flux:tab name="unofficial">{{ __('app.unofficial') }}</flux:tab>
                </flux:tabs>

                <flux:button
                    variant="primary"
                    color="zinc"
                    icon="arrow-right"
                    href="{{ route('panels.accounting.user.index') }}"
                    wire:navigate
                >
                    {{ __('app.back_to_users') }}
                </flux:button>
            </div>
        </div>

        <flux:separator variant="subtle" class="mt-6" />
    </div>

    @php $kpi = $this->performanceMetrics; @endphp

    <div class="mb-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef,selectedMonth">
        <flux:heading size="lg" class="mb-4">{{ __('app.sales_expert_performance_kpis') }}</flux:heading>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <flux:card class="border-t-4 border-sky-500">
                <flux:text>{{ __('app.sales_expert_customer_count') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($kpi['customer_count']) }}</flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-zinc-400">
                <flux:text>{{ __('app.sales_expert_proforma_count') }}</flux:text>
                <flux:heading size="xl" class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-teal-500">
                <flux:text>{{ __('app.sales_expert_invoice_count') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($kpi['invoice_count']) }}</flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-zinc-400">
                <flux:text>{{ __('app.sales_expert_lead_conversion_rate') }}</flux:text>
                <flux:heading size="xl" class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-emerald-500">
                <flux:text>{{ __('app.sales_expert_sold_quantity') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($kpi['sold_quantity']) }}</flux:heading>
            </flux:card>

            @can('accounting_profit_index')
                <flux:card class="border-t-4 border-amber-500">
                    <flux:text>{{ __('app.sales_expert_profit') }}</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums">
                        {{ number_format((float) ($kpi['profit'] ?? 0)) }}
                        <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                    </flux:heading>
                </flux:card>
            @endcan

            <flux:card class="border-t-4 border-indigo-500">
                <flux:text>{{ __('app.sales_expert_new_customers') }}</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($kpi['new_customer_count']) }}</flux:heading>
            </flux:card>

            <flux:card class="border-t-4 border-zinc-400">
                <flux:text>{{ __('app.sales_expert_buyer_type_frequency') }}</flux:text>
                <flux:heading size="xl" class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:heading>
            </flux:card>
        </div>
    </div>

    <flux:card class="mb-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef">
        <flux:heading size="lg" class="mb-4">{{ __('app.sales_expert_monthly_trend_chart') }}</flux:heading>

        <flux:chart :value="$this->monthlyTrend" class="h-80">
            <flux:chart.viewport class="min-h-[20rem]">
                <flux:chart.svg>
                    <flux:chart.group>
                        <flux:chart.bar field="invoices" class="text-sky-500" radius="4" />
                        <flux:chart.bar field="new_customers" class="text-indigo-500" radius="4" />
                    </flux:chart.group>

                    <flux:chart.axis axis="x" field="month">
                        <flux:chart.axis.tick />
                    </flux:chart.axis>

                    <flux:chart.axis axis="y" :format="['useGrouping' => true]">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>

                    <flux:chart.cursor type="area" />
                </flux:chart.svg>
            </flux:chart.viewport>

            <div class="flex justify-center gap-4 pt-4">
                <flux:chart.legend :label="__('app.sales_expert_invoice_count')">
                    <flux:chart.legend.indicator class="bg-sky-500" />
                </flux:chart.legend>
                <flux:chart.legend :label="__('app.sales_expert_new_customers')">
                    <flux:chart.legend.indicator class="bg-indigo-500" />
                </flux:chart.legend>
            </div>

            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="month" />
                <flux:chart.tooltip.value field="invoices" :label="__('app.sales_expert_invoice_count')" :format="['useGrouping' => true]" />
                <flux:chart.tooltip.value field="new_customers" :label="__('app.sales_expert_new_customers')" :format="['useGrouping' => true]" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>

    <flux:card class="panel-filter-card mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            <flux:input
                wire:model.live.debounce.400ms="startDate"
                :label="__('app.start_date')"
                mask="9999/99/99"
                placeholder="1404/01/01"
            />

            <flux:input
                wire:model.live.debounce.400ms="endDate"
                :label="__('app.end_date')"
                mask="9999/99/99"
                placeholder="1404/12/29"
            />

            <flux:input
                wire:model.live.debounce.400ms="search"
                icon="search"
                :label="__('app.search')"
                placeholder="{{ __('app.search_placeholder') }}"
            />

            <div class="flex items-end">
                <flux:button
                    variant="primary"
                    color="zinc"
                    class="w-full"
                    wire:click="clearFilters"
                >
                    {{ __('app.clear_all_filters') }}
                </flux:button>
            </div>
        </div>

        @if($startDate && $endDate && $this->gregorianDate($startDate) && $this->gregorianDate($endDate))
            <flux:text class="mt-3 text-sm text-zinc-500">
                {{ __('app.date_range_filter_hint', ['start' => $startDate, 'end' => $endDate]) }}
            </flux:text>
        @endif
    </flux:card>

    <div class="relative">
        <div wire:loading.delay.longer wire:target="selectedMonth, search, saleType, startDate, endDate, clearFilters, fiscalYearRef" class="absolute inset-0 bg-white/50 dark:bg-zinc-900/50 z-10 flex items-center justify-center backdrop-blur-sm rounded-xl">
            <flux:icon.loader-circle class="animate-spin text-zinc-500 w-10 h-10" />
        </div>

        <div class="space-y-6 mb-10">
            @php
                $monthly = $this->invoiceStats['monthly'];
                $maxAmount = max($monthly) ?: 0;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($monthly as $monthNumber => $amount)
                    @php
                        $isSelected = $selectedMonth === $monthNumber;
                        $colorClass = $isSelected
                            ? 'border-sky-500 bg-sky-50/50 dark:bg-sky-900/20 ring-2 ring-sky-500'
                            : ($amount > 0 && $amount == $maxAmount
                                ? 'border-green-500 bg-green-50/50 dark:bg-green-900/20'
                                : 'border-zinc-200 dark:border-zinc-700');
                    @endphp
                    <button
                        type="button"
                        wire:click="selectMonth({{ $monthNumber }})"
                        class="text-start rounded-xl border-t-4 transition-colors {{ $colorClass }}"
                    >
                        <flux:card class="border-0 shadow-none bg-transparent">
                            <flux:heading size="lg" class="mb-2">
                                {{ __('app.jalali_months.' . $monthNumber) }}
                            </flux:heading>
                            <flux:text size="xl" class="font-bold text-zinc-800 dark:text-zinc-100">
                                {{ number_format($amount) }}
                                <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                            </flux:text>
                        </flux:card>
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-zinc-500">
                    <div class="flex justify-between items-center">
                        <flux:heading size="lg">{{ __('app.total_user_invoices') }}</flux:heading>
                        <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                            {{ number_format($this->invoiceStats['total']) }}
                            <span class="text-lg font-bold">{{ __('app.rial') }}</span>
                        </flux:text>
                    </div>
                </flux:card>

                <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-t-4 border-sky-500">
                    <div class="flex justify-between items-center">
                        <flux:heading size="lg">{{ __('app.invoice_count') }}</flux:heading>
                        <flux:text size="2xl" class="font-black text-zinc-900 dark:text-white">
                            {{ number_format($this->invoiceStats['count']) }}
                        </flux:text>
                    </div>
                </flux:card>
            </div>
        </div>

        @if($selectedMonth)
            <flux:card class="mb-4 flex items-center justify-between gap-4">
                <flux:text>
                    {{ __('app.filtered_by_month') }}:
                    <span class="font-semibold">{{ __('app.jalali_months.' . $selectedMonth) }}</span>
                </flux:text>
                <flux:button size="sm" variant="ghost" wire:click="selectMonth({{ $selectedMonth }})">
                    {{ __('app.clear_filter') }}
                </flux:button>
            </flux:card>
        @endif

        <livewire:panels.accounting.invoice.view />

        <flux:table :paginate="$this->invoices">
            <flux:table.columns>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Number'" :direction="$sortDirection" wire:click="sort('Number')">{{ __('app.number') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'CustomerRealName'" :direction="$sortDirection" wire:click="sort('CustomerRealName')">{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.category') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Price'" :direction="$sortDirection" wire:click="sort('Price')">{{ __('app.price') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Date'" :direction="$sortDirection" wire:click="sort('Date')">{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->invoices as $invoice)
                    <flux:table.row :key="$invoice->InvoiceId">
                        <flux:table.cell class="w-1 whitespace-nowrap">
                            <flux:button
                                size="xs"
                                variant="primary"
                                color="sky"
                                wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { InvoiceId: '{{ $invoice->InvoiceId }}' })"
                            >
                                {{ __('app.view') }}
                            </flux:button>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $invoice->Number }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $invoice->CustomerRealName }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            @if($invoice->SaleTypeRef == 1)
                                {{ __('app.official') }}
                            @else
                                {{ __('app.unofficial') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ number_format($invoice->Price) }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $invoice->Date ? Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
