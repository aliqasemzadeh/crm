<?php

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\GNR\Grouping;
use App\Services\Warehouse\WarehouseDashboardService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    #[Url]
    public int|string $fiscalYearRef = '';

    #[Url]
    public int $selectedMonth = 0;

    #[Url]
    public string $stockRef = '';

    #[Url]
    public string $groupingFilter = '';

    public function mount(): void
    {
        $this->authorize('warehouse_dashboard_index');

        if ($this->fiscalYearRef === '' || $this->fiscalYearRef === null) {
            $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        }
    }

    public function updatedFiscalYearRef(): void
    {
        unset($this->metrics, $this->fiscalYears);
    }

    public function updatedSelectedMonth(): void
    {
        $this->selectedMonth = max(0, min(12, (int) $this->selectedMonth));
        unset($this->metrics);
    }

    public function updatedStockRef(): void
    {
        unset($this->metrics);
    }

    public function updatedGroupingFilter(): void
    {
        unset($this->metrics);
    }

    protected function monthFilter(): ?int
    {
        return $this->selectedMonth > 0 ? $this->selectedMonth : null;
    }

    protected function stockFilter(): ?int
    {
        return $this->stockRef !== '' ? (int) $this->stockRef : null;
    }

    protected function groupingFilterId(): ?int
    {
        return $this->groupingFilter !== '' ? (int) $this->groupingFilter : null;
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearID')->get();
    }

    #[Computed]
    public function stocks(): array
    {
        return app(WarehouseDashboardService::class)->stocks();
    }

    #[Computed]
    public function groupings(): Collection
    {
        $rows = Cache::remember('warehouse_item_groupings_v2', now()->addHours(1), function () {
            return Grouping::query()
                ->select(['GroupingID', 'Title'])
                ->whereNotNull('Title')
                ->orderBy('Title')
                ->get()
                ->map(fn (Grouping $grouping) => [
                    'GroupingID' => $grouping->GroupingID,
                    'Title' => $grouping->Title,
                ])
                ->all();
        });

        return collect($rows)->map(fn (array $row) => (object) $row);
    }

    #[Computed]
    public function metrics(): array
    {
        return app(WarehouseDashboardService::class)->metrics(
            (int) $this->fiscalYearRef,
            $this->monthFilter(),
            $this->stockFilter(),
            $this->groupingFilterId()
        );
    }
};
?>

<x-slot name="title">
    {{ __('app.warehouse_dashboard_title') }}
</x-slot>

@php
    $metrics = $this->metrics;
    $overview = $metrics['overview'];
@endphp

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.warehouse_dashboard_title') }}</flux:heading>
                <flux:subheading size="lg" class="mb-2">{{ __('app.warehouse_dashboard_goods_subtitle') }}</flux:subheading>
            </div>

            @can('warehouse_item_index')
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="primary" color="teal" href="{{ route('panels.warehouse.item.index') }}" wire:navigate icon="boxes" icon:variant="outline">
                        {{ __('app.warehouse_open_item_list') }}
                    </flux:button>
                    <flux:button variant="primary" color="sky" href="{{ route('panels.warehouse.history.index') }}" wire:navigate icon="calendar" icon:variant="outline">
                        {{ __('app.warehouse_history_nav') }}
                    </flux:button>
                </div>
            @endcan
        </div>
        <flux:separator variant="subtle" class="mt-4" />
    </div>

    <flux:card class="panel-filter-card mb-6">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
            <flux:field>
                <flux:label>{{ __('app.fiscal_year') }}</flux:label>
                <flux:select wire:model.live="fiscalYearRef" searchable>
                    @foreach($this->fiscalYears as $year)
                        <flux:select.option value="{{ $year->FiscalYearID }}">{{ $year->Title }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.month') }}</flux:label>
                <flux:select wire:model.live="selectedMonth" searchable>
                    <flux:select.option value="0">{{ __('app.whole_year') }}</flux:select.option>
                    @for($i = 1; $i <= 12; $i++)
                        <flux:select.option value="{{ $i }}">{{ __('app.jalali_months.'.$i) }}</flux:select.option>
                    @endfor
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.warehouse_stock') }}</flux:label>
                <flux:select wire:model.live="stockRef" searchable placeholder="{{ __('app.all_stocks') }}">
                    <flux:select.option value="">{{ __('app.all_stocks') }}</flux:select.option>
                    @foreach($this->stocks as $stock)
                        <flux:select.option value="{{ $stock['id'] }}">{{ $stock['title'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.grouping') }}</flux:label>
                <flux:select wire:model.live="groupingFilter" searchable placeholder="{{ __('app.all_groupings') }}">
                    <flux:select.option value="">{{ __('app.all_groupings') }}</flux:select.option>
                    @foreach($this->groupings as $grouping)
                        <flux:select.option value="{{ $grouping->GroupingID }}">{{ $grouping->Title }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('app.warehouse_item_type') }}</flux:label>
                <div class="mt-2">
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </div>
            </flux:field>
        </div>
    </flux:card>

    <div class="relative space-y-6" wire:loading.class="opacity-60" wire:target="fiscalYearRef,selectedMonth,stockRef,groupingFilter">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <flux:card class="border-t-4 border-emerald-500 p-5">
                <flux:subheading size="sm" class="text-zinc-500">{{ __('app.warehouse_inventory_value') }}</flux:subheading>
                <flux:heading size="xl" class="mt-2 tabular-nums">
                    {{ number_format($metrics['inventory_value']) }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('app.rial') }}</span>
                </flux:heading>
            </flux:card>

            <flux:card class="p-5">
                <flux:subheading size="sm" class="text-zinc-500">{{ __('app.all_items') }}</flux:subheading>
                <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($overview['total']) }}</flux:heading>
            </flux:card>

            <flux:card class="p-5">
                <flux:subheading size="sm" class="text-zinc-500">{{ __('app.without_image') }}</flux:subheading>
                <flux:heading size="xl" class="mt-2 tabular-nums text-amber-600 dark:text-amber-400">{{ number_format($overview['without_image']) }}</flux:heading>
            </flux:card>

            <flux:card class="p-5">
                <flux:subheading size="sm" class="text-zinc-500">{{ __('app.without_irancode') }}</flux:subheading>
                <flux:heading size="xl" class="mt-2 tabular-nums text-rose-600 dark:text-rose-400">{{ number_format($overview['without_irancode']) }}</flux:heading>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @include('pages.panels.warehouse.dashboard.partials.item-rank-card', [
                'title' => __('app.warehouse_top_items_by_quantity'),
                'rows' => $metrics['top_by_quantity'],
                'accent' => 'sky',
                'showProfit' => true,
            ])

            @include('pages.panels.warehouse.dashboard.partials.item-rank-card', [
                'title' => __('app.warehouse_top_items_by_profit'),
                'rows' => $metrics['top_by_profit'],
                'accent' => 'amber',
                'showProfit' => true,
            ])

            @include('pages.panels.warehouse.dashboard.partials.stagnant-card', [
                'title' => __('app.warehouse_stagnant_items'),
                'rows' => $metrics['stagnant_items'],
            ])

            @include('pages.panels.warehouse.dashboard.partials.group-rank-card', [
                'title' => __('app.warehouse_top_groups_by_sales'),
                'rows' => $metrics['top_groups_by_sales'],
                'accent' => 'teal',
                'mode' => 'sales',
            ])

            @include('pages.panels.warehouse.dashboard.partials.group-rank-card', [
                'title' => __('app.warehouse_top_groups_by_profit'),
                'rows' => $metrics['top_groups_by_profit'],
                'accent' => 'emerald',
                'mode' => 'profit',
            ])

            @if($metrics['top_brands_by_sales'] === null)
                <flux:card class="border-t-4 border-zinc-400">
                    <flux:heading size="lg" class="mb-3">{{ __('app.warehouse_top_brands_by_sales') }}</flux:heading>
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:card>
            @else
                @include('pages.panels.warehouse.dashboard.partials.group-rank-card', [
                    'title' => __('app.warehouse_top_brands_by_sales'),
                    'rows' => $metrics['top_brands_by_sales'],
                    'accent' => 'violet',
                    'mode' => 'sales',
                ])
            @endif

            @if($metrics['top_brands_by_profit'] === null)
                <flux:card class="border-t-4 border-zinc-400">
                    <flux:heading size="lg" class="mb-3">{{ __('app.warehouse_top_brands_by_profit') }}</flux:heading>
                    <flux:badge color="zinc" size="sm">{{ __('app.metric_unavailable') }}</flux:badge>
                </flux:card>
            @else
                @include('pages.panels.warehouse.dashboard.partials.group-rank-card', [
                    'title' => __('app.warehouse_top_brands_by_profit'),
                    'rows' => $metrics['top_brands_by_profit'],
                    'accent' => 'fuchsia',
                    'mode' => 'profit',
                ])
            @endif

            @include('pages.panels.warehouse.dashboard.partials.item-rank-card', [
                'title' => __('app.warehouse_top_items_organizational'),
                'rows' => $metrics['top_organizational'],
                'accent' => 'indigo',
                'showProfit' => true,
            ])

            @include('pages.panels.warehouse.dashboard.partials.item-rank-card', [
                'title' => __('app.warehouse_top_items_colleague'),
                'rows' => $metrics['top_colleague'],
                'accent' => 'cyan',
                'showProfit' => true,
            ])

            @include('pages.panels.warehouse.dashboard.partials.item-rank-card', [
                'title' => __('app.warehouse_top_items_users'),
                'rows' => $metrics['top_users'],
                'accent' => 'rose',
                'showProfit' => true,
            ])
        </div>
    </div>
</div>
