<?php

use App\Models\CurrencyRate;
use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\Item;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $groupingFilter = '';

    public string $stockFilter = '';

    public string $imageFilter = '';

    public string $sortBy = 'last_sale_desc';

    public int $noSaleMonths = 0;

    public int $noPurchaseMonths = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'groupingFilter' => ['except' => ''],
        'stockFilter' => ['except' => ''],
        'imageFilter' => ['except' => ''],
        'sortBy' => ['except' => 'last_sale_desc'],
        'noSaleMonths' => ['except' => 0],
        'noPurchaseMonths' => ['except' => 0],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('warehouse_item_index');
    }

    private function lastSaleDateSql(): string
    {
        return '(SELECT MAX(inv.[Date]) FROM [SLS].[InvoiceItem] AS ii INNER JOIN [SLS].[Invoice] AS inv ON inv.[InvoiceId] = ii.[InvoiceRef] WHERE ii.[ItemRef] = [INV].[Item].[ItemID])';
    }

    private function lastPurchaseDateSql(): string
    {
        return '(SELECT MAX(rec.[Date]) FROM [INV].[InventoryReceiptItem] AS ri INNER JOIN [INV].[InventoryReceipt] AS rec ON rec.[InventoryReceiptID] = ri.[InventoryReceiptRef] WHERE ri.[ItemRef] = [INV].[Item].[ItemID] AND rec.[IsReturn] = 0)';
    }

    private function lastPurchaseFeeSubquerySql(): string
    {
        return '(SELECT TOP 1 ri.[Fee] FROM [INV].[InventoryReceiptItem] AS ri INNER JOIN [INV].[InventoryReceipt] AS rec ON rec.[InventoryReceiptID] = ri.[InventoryReceiptRef] AND rec.[IsReturn] = 0 WHERE ri.[ItemRef] = [INV].[Item].[ItemID] ORDER BY ri.[InventoryReceiptItemID] DESC)';
    }

    private function lastPurchaseDateByLineSubquerySql(): string
    {
        return '(SELECT TOP 1 rec.[Date] FROM [INV].[InventoryReceiptItem] AS ri INNER JOIN [INV].[InventoryReceipt] AS rec ON rec.[InventoryReceiptID] = ri.[InventoryReceiptRef] AND rec.[IsReturn] = 0 WHERE ri.[ItemRef] = [INV].[Item].[ItemID] ORDER BY ri.[InventoryReceiptItemID] DESC)';
    }

    private function stockQuantitySubquerySql(): string
    {
        return '(SELECT COALESCE(SUM(CAST(s.[Quantity] AS DECIMAL(18,4))), 0) FROM [INV].[ItemStockSummary] s WHERE s.[ItemRef] = [INV].[Item].[ItemID] AND s.[FiscalYearRef] = ?)';
    }

    private function applySearchAndGrouping(Builder $query): void
    {
        $query
            ->when($this->search !== '', function (Builder $q) {
                $search = '%'.$this->search.'%';
                $q->where('Title', 'like', $search);
            })
            ->when($this->groupingFilter !== '', function (Builder $q) {
                $q->where('CodingGroupRef', $this->groupingFilter);
            });
    }

    private function groupingTitleSubquerySql(): string
    {
        return '(SELECT TOP 1 g.[Title] FROM [GNR].[Grouping] AS g WHERE g.[GroupingID] = [INV].[Item].[CodingGroupRef])';
    }

    public function sortColumnIsActive(string $column): bool
    {
        return match ($column) {
            'last_sale' => str_starts_with($this->sortBy, 'last_sale'),
            'last_purchase' => str_starts_with($this->sortBy, 'last_purchase'),
            'grouping' => str_starts_with($this->sortBy, 'grouping'),
            default => false,
        };
    }

    public function sortColumnDirectionFor(string $column): string
    {
        if (! $this->sortColumnIsActive($column)) {
            return 'desc';
        }

        return str_ends_with($this->sortBy, '_asc') ? 'asc' : 'desc';
    }

    public function sortTable(string $column): void
    {
        match ($column) {
            'last_sale' => $this->sortBy = match ($this->sortBy) {
                'last_sale_desc' => 'last_sale_asc',
                'last_sale_asc' => 'last_sale_desc',
                default => 'last_sale_desc',
            },
            'last_purchase' => $this->sortBy = match ($this->sortBy) {
                'last_purchase_desc' => 'last_purchase_asc',
                'last_purchase_asc' => 'last_purchase_desc',
                default => 'last_purchase_desc',
            },
            'grouping' => $this->sortBy = match ($this->sortBy) {
                'grouping_desc' => 'grouping_asc',
                'grouping_asc' => 'grouping_desc',
                default => 'grouping_asc',
            },
            default => null,
        };

        $this->resetPage();
    }

    private function applyImageFilter(Builder $query): void
    {
        match ($this->imageFilter) {
            'has' => $query->has('image'),
            'none' => $query->doesntHave('image'),
            default => null,
        };
    }

    private function applyStockFilter(Builder $query, string $fiscalYearRef): void
    {
        $sql = $this->stockQuantitySubquerySql();

        match ($this->stockFilter) {
            'in_stock' => $query->whereInStock($fiscalYearRef),
            'out_of_stock' => $query->whereRaw("{$sql} <= 0", [$fiscalYearRef]),
            'low_stock' => $query->whereRaw("{$sql} > 0 AND {$sql} < ?", [$fiscalYearRef, $fiscalYearRef, 10]),
            default => null,
        };
    }

    private function applyStaleSaleFilter(Builder $query): void
    {
        if ($this->noSaleMonths <= 0) {
            return;
        }

        $cutoff = now()->subMonths($this->noSaleMonths)->startOfDay();
        $saleSql = $this->lastSaleDateSql();

        $query->whereRaw("({$saleSql} IS NULL OR {$saleSql} < ?)", [$cutoff, $cutoff]);
    }

    private function applyStalePurchaseFilter(Builder $query): void
    {
        if ($this->noPurchaseMonths <= 0) {
            return;
        }

        $cutoff = now()->subMonths($this->noPurchaseMonths)->startOfDay();
        $purchaseSql = $this->lastPurchaseDateSql();

        $query->whereRaw("({$purchaseSql} IS NULL OR {$purchaseSql} < ?)", [$cutoff, $cutoff]);
    }

    private function applySort(Builder $query): void
    {
        $saleSql = $this->lastSaleDateSql();
        $purchaseSql = $this->lastPurchaseDateSql();
        $groupingTitleSql = $this->groupingTitleSubquerySql();

        match ($this->sortBy) {
            'last_sale_desc' => $query
                ->orderByRaw("CASE WHEN {$saleSql} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$saleSql} DESC")
                ->orderByDesc('ItemID'),
            'last_sale_asc' => $query
                ->orderByRaw("CASE WHEN {$saleSql} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$saleSql} ASC")
                ->orderByDesc('ItemID'),
            'last_purchase_desc' => $query
                ->orderByRaw("CASE WHEN {$purchaseSql} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$purchaseSql} DESC")
                ->orderByDesc('ItemID'),
            'last_purchase_asc' => $query
                ->orderByRaw("CASE WHEN {$purchaseSql} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$purchaseSql} ASC")
                ->orderByDesc('ItemID'),
            'grouping_asc' => $query
                ->orderByRaw("CASE WHEN {$groupingTitleSql} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$groupingTitleSql} ASC")
                ->orderByDesc('ItemID'),
            'grouping_desc' => $query
                ->orderByRaw("CASE WHEN {$groupingTitleSql} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$groupingTitleSql} DESC")
                ->orderByDesc('ItemID'),
            default => $query->orderBy('CreationDate', 'desc')->orderByDesc('ItemID'),
        };
    }

    /**
     * @return array{then: ?float, now: ?float}
     */
    public function lastPurchaseUsdtValues(Item $item): array
    {
        $fee = (float) ($item->warehouse_last_purchase_fee ?? 0);
        $dateRaw = $item->warehouse_last_purchase_date ?? null;
        if ($fee <= 0 || empty($dateRaw)) {
            return ['then' => null, 'now' => null];
        }

        $purchaseDate = Carbon::parse($dateRaw);
        $rateThen = CurrencyRate::getRate($purchaseDate);
        $rateNow = CurrencyRate::getRate(now());
        $tomanPerUsdtThen = $rateThen ? (float) $rateThen / 10 : 0.0;
        $tomanPerUsdtNow = $rateNow ? (float) $rateNow / 10 : 0.0;

        $then = $tomanPerUsdtThen > 0 ? $fee / $tomanPerUsdtThen : null;
        $now = $tomanPerUsdtNow > 0 ? $fee / $tomanPerUsdtNow : null;

        return ['then' => $then, 'now' => $now];
    }

    public function formatUsdtDisplay(?float $usdt): string
    {
        if ($usdt === null || $usdt <= 0) {
            return '—';
        }

        if ($usdt < 1000) {
            return number_format($usdt, 2, '.', ',').' USDT';
        }

        $thousands = $usdt / 1000;
        if (abs($thousands - round($thousands)) < 0.05) {
            return (string) (int) round($thousands).'K USDT';
        }

        $formatted = number_format($thousands, 1, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted.'K USDT';
    }

    public function formatSignedUsdtSummary(float $value): string
    {
        $sign = $value > 0 ? '+' : ($value < 0 ? '-' : '');

        return $sign.number_format(abs($value), 2, '.', ',').' USDT';
    }

    #[Computed]
    public function groupings(): Collection
    {
        return Cache::remember('warehouse_item_groupings', now()->addHours(1), function () {
            return Grouping::query()
                ->select(['GroupingID', 'Title'])
                ->whereNotNull('Title')
                ->orderBy('Title')
                ->get();
        });
    }

    private function historyItemsBaseQuery(bool $withRelations = true): Builder
    {
        $fiscalYearRef = (string) config('sepidar.FiscalYearRef');
        $saleSql = $this->lastSaleDateSql();
        $purchaseSql = $this->lastPurchaseDateSql();
        $feeSql = $this->lastPurchaseFeeSubquerySql();
        $purchaseLineDateSql = $this->lastPurchaseDateByLineSubquerySql();
        $itemTable = (new Item)->getTable();

        return Item::query()
            ->select($itemTable.'.*')
            ->addSelect([
                DB::raw("{$saleSql} AS last_sale_date"),
                DB::raw("{$purchaseSql} AS last_purchase_date"),
                DB::raw("{$feeSql} AS warehouse_last_purchase_fee"),
                DB::raw("{$purchaseLineDateSql} AS warehouse_last_purchase_date"),
            ])
            ->when($withRelations, function (Builder $q) {
                $q->with([
                    'grouping:GroupingID,Title',
                    'image:ItemRef,Thumbnail',
                ]);
            })
            ->withSum([
                'stockSummaries as stock_quantity' => function ($query) use ($fiscalYearRef) {
                    $query->where('FiscalYearRef', $fiscalYearRef);
                },
            ], 'Quantity')
            ->tap(fn (Builder $q) => $this->applySearchAndGrouping($q))
            ->tap(fn (Builder $q) => $this->applyImageFilter($q))
            ->tap(fn (Builder $q) => $this->applyStockFilter($q, $fiscalYearRef))
            ->tap(fn (Builder $q) => $this->applyStaleSaleFilter($q))
            ->tap(fn (Builder $q) => $this->applyStalePurchaseFilter($q))
            ->tap(fn (Builder $q) => $this->applySort($q));
    }

    #[Computed]
    public function items()
    {
        return $this->historyItemsBaseQuery()->paginate(100);
    }

    /**
     * @return array{total: float, lines: int}
     */
    #[Computed]
    public function purchaseFxPnlUsdtSummary(): array
    {
        $total = 0.0;
        $lines = 0;

        foreach ($this->historyItemsBaseQuery(false)->orderBy('ItemID')->cursor() as $item) {
            $qty = (float) ($item->stock_quantity ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $usdt = $this->lastPurchaseUsdtValues($item);
            if ($usdt['then'] === null || $usdt['now'] === null) {
                continue;
            }

            $total += ($usdt['now'] - $usdt['then']) * $qty;
            $lines++;
        }

        return ['total' => $total, 'lines' => $lines];
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->groupingFilter = '';
        $this->stockFilter = '';
        $this->imageFilter = '';
        $this->sortBy = 'last_sale_desc';
        $this->noSaleMonths = 0;
        $this->noPurchaseMonths = 0;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGroupingFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingImageFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function updatingNoSaleMonths(): void
    {
        $this->resetPage();
    }

    public function updatingNoPurchaseMonths(): void
    {
        $this->resetPage();
    }

    #[On('panels.warehouse.item.edit-site.saved')]
    public function refreshAfterSiteCodeUpdate(): void
    {
        unset($this->items, $this->purchaseFxPnlUsdtSummary);
    }

    #[On('panels.warehouse.item.upload-image.saved')]
    public function refreshAfterItemImageUpload(): void
    {
        unset($this->items, $this->purchaseFxPnlUsdtSummary);
    }
};
?>

<x-slot name="title">
    {{ __('app.warehouse_history_title') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.warehouse_history_title') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.warehouse_history_description') }}</flux:subheading>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:card class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-3">
            <flux:input
                wire:model.live.debounce.400ms="search"
                placeholder="{{ __('app.warehouse_history_search_placeholder') }}"
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="groupingFilter" searchable placeholder="{{ __('app.grouping') }}">
                <option value="">{{ __('app.all_groupings') }}</option>
                @foreach($this->groupings as $grouping)
                    <option value="{{ $grouping->GroupingID }}">{{ $grouping->Title }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="stockFilter" searchable placeholder="{{ __('app.warehouse_stock_filter') }}">
                <option value="">{{ __('app.warehouse_stock_all') }}</option>
                <option value="in_stock">{{ __('app.warehouse_stock_in_stock') }}</option>
                <option value="low_stock">{{ __('app.warehouse_stock_low') }}</option>
                <option value="out_of_stock">{{ __('app.warehouse_stock_out') }}</option>
            </flux:select>

            <flux:select wire:model.live="imageFilter" searchable placeholder="{{ __('app.warehouse_history_image_filter') }}">
                <option value="">{{ __('app.warehouse_history_image_all') }}</option>
                <option value="has">{{ __('app.warehouse_history_image_has') }}</option>
                <option value="none">{{ __('app.warehouse_history_image_none') }}</option>
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-3">
            <flux:select wire:model.live="sortBy" searchable placeholder="{{ __('app.warehouse_history_sort_label') }}">
                <option value="last_sale_desc">{{ __('app.warehouse_history_sort_last_sale_desc') }}</option>
                <option value="last_sale_asc">{{ __('app.warehouse_history_sort_last_sale_asc') }}</option>
                <option value="last_purchase_desc">{{ __('app.warehouse_history_sort_last_purchase_desc') }}</option>
                <option value="last_purchase_asc">{{ __('app.warehouse_history_sort_last_purchase_asc') }}</option>
                <option value="grouping_asc">{{ __('app.warehouse_history_sort_grouping_asc') }}</option>
                <option value="grouping_desc">{{ __('app.warehouse_history_sort_grouping_desc') }}</option>
                <option value="created_desc">{{ __('app.warehouse_history_sort_created_desc') }}</option>
            </flux:select>

            <div class="space-y-1">
                <flux:select wire:model.live="noSaleMonths" label="{{ __('app.warehouse_history_no_sale_months') }}">
                    <option value="0">{{ __('app.warehouse_history_months_off') }}</option>
                    <option value="1">۱</option>
                    <option value="2">۲</option>
                    <option value="3">۳</option>
                    <option value="6">۶</option>
                    <option value="12">۱۲</option>
                    <option value="18">۱۸</option>
                    <option value="24">۲۴</option>
                </flux:select>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('app.warehouse_history_no_sale_months_hint') }}</flux:text>
            </div>

            <div class="space-y-1">
                <flux:select wire:model.live="noPurchaseMonths" label="{{ __('app.warehouse_history_no_purchase_months') }}">
                    <option value="0">{{ __('app.warehouse_history_months_off') }}</option>
                    <option value="1">۱</option>
                    <option value="2">۲</option>
                    <option value="3">۳</option>
                    <option value="6">۶</option>
                    <option value="12">۱۲</option>
                    <option value="18">۱۸</option>
                    <option value="24">۲۴</option>
                </flux:select>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('app.warehouse_history_no_purchase_months_hint') }}</flux:text>
            </div>

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
    </flux:card>

    <div class="relative min-h-[16rem]">
        <div
            wire:loading.delay.shortest
            class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-xl bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm"
        >
            <flux:icon icon="loading" class="size-9 text-teal-600 dark:text-teal-400" />
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('app.loading') }}</flux:text>
        </div>

        <flux:table :paginate="$this->items">
            <flux:table.columns>
                <flux:table.column>{{ __('app.warehouse_item_site_and_media_column') }}</flux:table.column>
                <flux:table.column>{{ __('app.code') }}</flux:table.column>
                <flux:table.column>{{ __('app.title') }}</flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$this->sortColumnIsActive('grouping')"
                    :direction="$this->sortColumnDirectionFor('grouping')"
                    wire:click="sortTable('grouping')"
                >{{ __('app.grouping') }}</flux:table.column>
                <flux:table.column>{{ __('app.stock') }}</flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$this->sortColumnIsActive('last_sale')"
                    :direction="$this->sortColumnDirectionFor('last_sale')"
                    wire:click="sortTable('last_sale')"
                >{{ __('app.warehouse_history_last_sale') }}</flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$this->sortColumnIsActive('last_purchase')"
                    :direction="$this->sortColumnDirectionFor('last_purchase')"
                    wire:click="sortTable('last_purchase')"
                >{{ __('app.warehouse_item_last_purchase_fee') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->items as $item)
                    <flux:table.row :key="$item->ItemID">
                        <flux:table.cell class="align-top max-w-[16rem]">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-start gap-2">
                                    @if($item->image?->Thumbnail)
                                        <img
                                            src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}"
                                            alt="{{ $item->Title }}"
                                            class="w-12 h-12 shrink-0 rounded-md object-cover border border-zinc-200 dark:border-zinc-700"
                                        />
                                    @else
                                        <div class="w-12 h-12 shrink-0 bg-zinc-100 dark:bg-zinc-800 rounded-md flex items-center justify-center">
                                            <flux:icon icon="image-off" class="text-zinc-400" />
                                        </div>
                                    @endif
                                    <div class="flex flex-col gap-1.5 min-w-0">
                                        <div class="flex flex-wrap gap-1">
                                            <flux:badge color="{{ $item->image?->Thumbnail ? 'emerald' : 'zinc' }}">
                                                {{ $item->image?->Thumbnail ? __('app.warehouse_item_image_badge_set') : __('app.warehouse_item_image_badge_missing') }}
                                            </flux:badge>
                                            <flux:badge color="{{ $item->IranCode ? 'sky' : 'rose' }}">
                                                {{ $item->IranCode ? __('app.warehouse_item_site_code_badge_set') : __('app.warehouse_item_site_code_badge_missing') }}
                                            </flux:badge>
                                        </div>
                                        @if($item->IranCode)
                                            <span class="text-xs text-zinc-800 dark:text-zinc-100 break-all">{{ $item->IranCode }}</span>
                                        @endif
                                        <div class="flex flex-wrap gap-1">
                                            <flux:tooltip content="{{ __('app.warehouse_item_tooltip_edit_site') }}">
                                                <flux:button
                                                    size="xs"
                                                    variant="primary"
                                                    color="sky"
                                                    icon="pencil"
                                                    icon:variant="outline"
                                                    type="button"
                                                    wire:click="$dispatch('panels.warehouse.item.edit-site.assign-data', { id: {{ $item->ItemID }} }})"
                                                />
                                            </flux:tooltip>
                                            <flux:tooltip content="{{ __('app.warehouse_item_upload_image') }}">
                                                <flux:button
                                                    size="xs"
                                                    variant="primary"
                                                    color="teal"
                                                    icon="upload"
                                                    icon:variant="outline"
                                                    type="button"
                                                    wire:click="$dispatch('panels.warehouse.item.upload-image.assign-data', { id: {{ $item->ItemID }} }})"
                                                />
                                            </flux:tooltip>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">{{ $item->Code }}</flux:table.cell>
                        <flux:table.cell>{{ $item->Title }}</flux:table.cell>
                        <flux:table.cell>{{ $item->grouping->Title ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $item->stock_quantity, 2) }}</flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">
                            @if($item->last_sale_date)
                                {{ \Morilog\Jalali\Jalalian::fromDateTime($item->last_sale_date)->format('%Y-%m-%d') }}
                            @else
                                <flux:badge color="zinc">{{ __('app.warehouse_history_never_sold') }}</flux:badge>
                            @endif
                        </flux:table.cell>

                        @php
                            $feeRial = (float) ($item->warehouse_last_purchase_fee ?? 0);
                            $purchaseDateRaw = $item->warehouse_last_purchase_date ?? null;
                            $usdt = $this->lastPurchaseUsdtValues($item);
                        @endphp
                        <flux:table.cell class="align-top text-xs leading-relaxed max-w-[14rem]">
                            @if($feeRial > 0 && $purchaseDateRaw)
                                <div class="space-y-1">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ \Morilog\Jalali\Jalalian::fromDateTime(\Carbon\Carbon::parse($purchaseDateRaw))->format('%Y-%m-%d') }}
                                    </div>
                                    <div>
                                        {{ number_format($feeRial / 10) }}
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('app.toman') }}</span>
                                    </div>
                                    <div class="text-zinc-600 dark:text-zinc-300">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('app.warehouse_item_last_purchase_usdt_then') }}:</span>
                                        {{ $this->formatUsdtDisplay($usdt['then']) }}
                                    </div>
                                    <div class="text-zinc-600 dark:text-zinc-300">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('app.warehouse_item_last_purchase_usdt_now') }}:</span>
                                        {{ $this->formatUsdtDisplay($usdt['now']) }}
                                    </div>
                                </div>
                            @elseif($item->last_purchase_date)
                                <div class="space-y-1">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ \Morilog\Jalali\Jalalian::fromDateTime($item->last_purchase_date)->format('%Y-%m-%d') }}
                                    </div>
                                    <flux:badge color="zinc">{{ __('app.warehouse_item_last_purchase_no_record') }}</flux:badge>
                                </div>
                            @else
                                <flux:badge color="zinc">{{ __('app.warehouse_history_never_purchased') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    @php
        $pnl = $this->purchaseFxPnlUsdtSummary;
    @endphp
    <flux:card class="mt-4">
        <flux:heading size="md" class="mb-1">{{ __('app.warehouse_purchase_fx_pnl_usdt_title') }}</flux:heading>
        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">{{ __('app.warehouse_purchase_fx_pnl_usdt_hint') }}</flux:text>
        <div class="flex flex-wrap items-baseline gap-2">
            <flux:heading size="xl" class="{{ $pnl['total'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                {{ $this->formatSignedUsdtSummary($pnl['total']) }}
            </flux:heading>
            <flux:badge color="zinc">{{ __('app.warehouse_purchase_fx_pnl_lines', ['count' => number_format($pnl['lines'])]) }}</flux:badge>
        </div>
    </flux:card>

    <livewire:panels.warehouse.item.edit-site />
    <livewire:panels.warehouse.item.upload-image />
</div>
