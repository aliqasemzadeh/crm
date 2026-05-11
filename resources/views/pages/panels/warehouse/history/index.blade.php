<?php

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
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

    private function stockQuantitySubquerySql(): string
    {
        return '(SELECT COALESCE(SUM(CAST(s.[Quantity] AS DECIMAL(18,4))), 0) FROM [INV].[ItemStockSummary] s WHERE s.[ItemRef] = [INV].[Item].[ItemID] AND s.[FiscalYearRef] = ?)';
    }

    private function applySearchAndGrouping(Builder $query): void
    {
        $query
            ->when($this->search !== '', function (Builder $q) {
                $search = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('Code', 'like', $search)
                        ->orWhere('Title', 'like', $search)
                        ->orWhere('IranCode', 'like', $search);
                });
            })
            ->when($this->groupingFilter !== '', function (Builder $q) {
                $q->where('CodingGroupRef', $this->groupingFilter);
            });
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
            default => $query->orderBy('CreationDate', 'desc')->orderByDesc('ItemID'),
        };
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

    #[Computed]
    public function items()
    {
        $fiscalYearRef = (string) config('sepidar.FiscalYearRef');
        $saleSql = $this->lastSaleDateSql();
        $purchaseSql = $this->lastPurchaseDateSql();
        $itemTable = (new Item)->getTable();

        return Item::query()
            ->select($itemTable.'.*')
            ->addSelect([
                DB::raw("{$saleSql} AS last_sale_date"),
                DB::raw("{$purchaseSql} AS last_purchase_date"),
            ])
            ->with([
                'grouping:GroupingID,Title',
                'image:ItemRef,Thumbnail',
            ])
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
            ->tap(fn (Builder $q) => $this->applySort($q))
            ->paginate(20);
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

    <flux:table :paginate="$this->items">
        <flux:table.columns>
            <flux:table.column>{{ __('app.image') }}</flux:table.column>
            <flux:table.column>{{ __('app.code') }}</flux:table.column>
            <flux:table.column>{{ __('app.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.grouping') }}</flux:table.column>
            <flux:table.column>{{ __('app.stock') }}</flux:table.column>
            <flux:table.column>{{ __('app.warehouse_history_last_sale') }}</flux:table.column>
            <flux:table.column>{{ __('app.warehouse_history_last_purchase') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->items as $item)
                <flux:table.row :key="$item->ItemID">
                    <flux:table.cell>
                        @if($item->image?->Thumbnail)
                            <img
                                src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}"
                                alt="{{ $item->Title }}"
                                class="w-12 h-12 rounded-md object-cover border border-zinc-200 dark:border-zinc-700"
                            />
                        @else
                            <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-md flex items-center justify-center">
                                <flux:icon icon="image-off" class="text-zinc-400" />
                            </div>
                        @endif
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

                    <flux:table.cell class="whitespace-nowrap">
                        @if($item->last_purchase_date)
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($item->last_purchase_date)->format('%Y-%m-%d') }}
                        @else
                            <flux:badge color="zinc">{{ __('app.warehouse_history_never_purchased') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
