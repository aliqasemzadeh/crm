<?php

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\FMK\User as SepidarUser;
use App\Services\Accounting\SalesExpertPerformanceService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
new #[Layout('layouts.panels.accounting')] class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public int|string $fiscalYearRef = '';

    #[Url]
    public int $selectedMonth = 0;

    public string $sortBy = 'invoice_count';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'fiscalYearRef' => ['except' => ''],
        'selectedMonth' => ['except' => 0],
    ];

    public function mount(): void
    {
        $this->authorize('accounting_user_index');

        if ($this->fiscalYearRef === '' || $this->fiscalYearRef === null) {
            $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        }
    }

    public function updatedFiscalYearRef(): void
    {
        $this->resetPage();
        unset($this->users, $this->metrics, $this->comparisonChart, $this->fiscalYears);
    }

    public function updatedSelectedMonth(): void
    {
        $this->selectedMonth = max(0, min(12, (int) $this->selectedMonth));
        $this->resetPage();
        unset($this->users, $this->metrics, $this->comparisonChart);
    }

    protected function monthFilter(): ?int
    {
        return $this->selectedMonth > 0 ? $this->selectedMonth : null;
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        unset($this->users);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearID')->get();
    }

    #[Computed]
    public function users()
    {
        $fiscalYearRef = (int) $this->fiscalYearRef;
        [$start, $end] = app(SalesExpertPerformanceService::class)->periodBounds($fiscalYearRef, $this->monthFilter());

        return SepidarUser::query()
            ->where('IsDeleted', 0)
            ->withCount([
                'invoices as invoice_count' => function (Builder $query) use ($fiscalYearRef, $start, $end) {
                    $query->where('FiscalYearRef', $fiscalYearRef)
                        ->where('State', '<>', 2);

                    if ($start && $end) {
                        $query->whereBetween('Date', [$start, $end]);
                    }
                },
            ])
            ->when($this->search !== '', function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('Name', 'like', $search)
                        ->orWhere('Name_En', 'like', $search)
                        ->orWhere('UserName', 'like', $search);
                });
            })
            ->when($this->sortBy === 'invoice_count', function (Builder $query) {
                $query->orderBy('invoice_count', $this->sortDirection);
            })
            ->when($this->sortBy === 'Name', function (Builder $query) {
                $query->orderBy('Name', $this->sortDirection);
            })
            ->when($this->sortBy === 'UserName', function (Builder $query) {
                $query->orderBy('UserName', $this->sortDirection);
            })
            ->when($this->sortBy === 'CreationDate', function (Builder $query) {
                $query->orderBy('CreationDate', $this->sortDirection);
            })
            ->paginate(50);
    }

    #[Computed]
    public function metrics(): array
    {
        $creatorIds = $this->users->getCollection()->pluck('UserID')->map(fn ($id) => (int) $id)->all();

        return app(SalesExpertPerformanceService::class)->metricsForCreators(
            $creatorIds,
            (int) $this->fiscalYearRef,
            $this->monthFilter(),
            auth()->user()?->can('accounting_profit_index') ?? false
        );
    }

    #[Computed]
    public function comparisonChart(): array
    {
        return app(SalesExpertPerformanceService::class)->comparisonChart(
            (int) $this->fiscalYearRef,
            $this->monthFilter()
        );
    }

    public function metricFor(int $userId): array
    {
        return $this->metrics[$userId] ?? [
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
};
?>

<x-slot name="title">
    {{ __('app.accounting_users') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.accounting_users') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">{{ __('app.accounting_users_description') }}</flux:subheading>
            </div>

            <div class="flex flex-wrap items-center gap-2">
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
            </div>
        </div>

        <flux:separator variant="subtle" class="mt-4" />
    </div>

    <flux:card class="panel-filter-card mb-4">
        <flux:input
            wire:model.live.debounce.400ms="search"
            icon="search"
            placeholder="{{ __('app.search_placeholder') }}"
        />
    </flux:card>

    @if(count($this->comparisonChart) > 0)
        <flux:card class="mb-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef,selectedMonth,search">
            <flux:heading size="lg" class="mb-4">{{ __('app.sales_expert_comparison_chart') }}</flux:heading>

            <flux:chart :value="$this->comparisonChart" class="h-80">
                <flux:chart.viewport class="min-h-[20rem]">
                    <flux:chart.svg>
                        <flux:chart.bar field="invoices" class="text-sky-500" radius="4" width="70%" />

                        <flux:chart.axis axis="x" field="name">
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :format="['useGrouping' => true]">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor type="area" />
                    </flux:chart.svg>
                </flux:chart.viewport>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="name" />
                    <flux:chart.tooltip.value field="invoices" :label="__('app.sales_expert_invoice_count')" :format="['useGrouping' => true]" />
                </flux:chart.tooltip>
            </flux:chart>
        </flux:card>
    @endif

    <div class="relative" wire:loading.class="opacity-60" wire:target="fiscalYearRef,selectedMonth,search,sort">
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Name'" :direction="$sortDirection" wire:click="sort('Name')">{{ __('app.name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'UserName'" :direction="$sortDirection" wire:click="sort('UserName')">{{ __('app.username') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales_expert_customer_count') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales_expert_proforma_count') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'invoice_count'" :direction="$sortDirection" wire:click="sort('invoice_count')">{{ __('app.sales_expert_invoice_count') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales_expert_lead_conversion_rate') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales_expert_sold_quantity') }}</flux:table.column>
                @can('accounting_profit_index')
                    <flux:table.column>{{ __('app.sales_expert_profit') }}</flux:table.column>
                @endcan
                <flux:table.column>{{ __('app.sales_expert_new_customers') }}</flux:table.column>
                <flux:table.column>{{ __('app.sales_expert_buyer_type_frequency') }}</flux:table.column>
                <flux:table.column>{{ __('app.status') }}</flux:table.column>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $index => $user)
                    @php
                        $metric = $this->metricFor((int) $user->UserID);
                    @endphp
                    <flux:table.row :key="$user->UserID">
                        <flux:table.cell>{{ $this->users->firstItem() + $index }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $user->Name ?: '-' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $user->UserName }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format($metric['customer_count']) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format($metric['invoice_count']) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format($metric['sold_quantity']) }}</flux:table.cell>
                        @can('accounting_profit_index')
                            <flux:table.cell class="tabular-nums whitespace-nowrap">
                                {{ number_format((float) ($metric['profit'] ?? 0)) }}
                                <span class="text-xs text-zinc-500">{{ __('app.rial') }}</span>
                            </flux:table.cell>
                        @endcan
                        <flux:table.cell class="tabular-nums">{{ number_format($metric['new_customer_count']) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $user->Status ? 'emerald' : 'zinc' }}" size="sm">
                                {{ $user->Status ? __('app.active') : __('app.inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="w-1 whitespace-nowrap">
                            <flux:tooltip content="{{ __('app.user_invoices_report') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="sky"
                                    icon="chart-column"
                                    icon:variant="outline"
                                    href="{{ route('panels.accounting.user.report', ['sepidarUser' => $user->UserID, 'fiscalYearRef' => $fiscalYearRef, 'selectedMonth' => $selectedMonth]) }}"
                                    wire:navigate
                                />
                            </flux:tooltip>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
