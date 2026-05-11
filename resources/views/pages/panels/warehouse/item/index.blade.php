<?php

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    public string $statusFilter = '';

    public string $stockFilter = '';

    public string $iranCodeFilter = '';

    public string $codePrefix = '';

    public string $sortBy = 'created_desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'groupingFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'stockFilter' => ['except' => ''],
        'iranCodeFilter' => ['except' => ''],
        'codePrefix' => ['except' => ''],
        'sortBy' => ['except' => 'created_desc'],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('warehouse_item_index');
    }

    private function applyCommonFilters(Builder $query, ?string $fiscalYearRef = null): void
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
            ->when($this->codePrefix !== '', function (Builder $q) {
                $q->where('Code', 'like', $this->codePrefix.'%');
            })
            ->when($this->groupingFilter !== '', function (Builder $q) {
                $q->where('CodingGroupRef', $this->groupingFilter);
            })
            ->when($this->statusFilter === 'without_image', function (Builder $q) {
                $q->doesntHave('image');
            })
            ->when($this->statusFilter === 'without_irancode', function (Builder $q) {
                $q->whereNull('IranCode');
            })
            ->when($this->iranCodeFilter === 'has', function (Builder $q) {
                $q->whereNotNull('IranCode')->where('IranCode', '!=', '');
            })
            ->when($this->iranCodeFilter === 'none', function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNull('IranCode')->orWhere('IranCode', '=', '');
                });
            });

        if ($fiscalYearRef !== null) {
            $this->applyStockFilter($query, $fiscalYearRef);
        }
    }

    private function stockQuantitySubquerySql(): string
    {
        return '(SELECT COALESCE(SUM(CAST(s.[Quantity] AS DECIMAL(18,4))), 0) FROM [INV].[ItemStockSummary] s WHERE s.[ItemRef] = [INV].[Item].[ItemID] AND s.[FiscalYearRef] = ?)';
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

    private function applySort(Builder $query, string $fiscalYearRef): void
    {
        $sql = $this->stockQuantitySubquerySql();

        match ($this->sortBy) {
            'created_asc' => $query->orderBy('CreationDate', 'asc'),
            'title_asc' => $query->orderBy('Title', 'asc'),
            'title_desc' => $query->orderBy('Title', 'desc'),
            'code_asc' => $query->orderBy('Code', 'asc'),
            'code_desc' => $query->orderBy('Code', 'desc'),
            'stock_desc' => $query->orderByRaw("{$sql} DESC", [$fiscalYearRef])->orderBy('ItemID', 'desc'),
            'stock_asc' => $query->orderByRaw("{$sql} ASC", [$fiscalYearRef]),
            'without_image_first' => $query
                ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM [INV].[ItemImage] WHERE [INV].[ItemImage].[ItemRef] = [INV].[Item].[ItemID]) THEN 1 ELSE 0 END ASC')
                ->orderBy('CreationDate', 'desc'),
            default => $query->orderBy('CreationDate', 'desc'),
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
    public function stats(): Collection
    {
        $fiscalYearRef = (string) config('sepidar.FiscalYearRef');

        $baseQuery = Item::query()->whereInStock($fiscalYearRef);
        $this->applyCommonFilters($baseQuery, $fiscalYearRef);

        $total = (clone $baseQuery)->count();
        $withoutImage = (clone $baseQuery)->doesntHave('image')->count();
        $withoutIranCode = (clone $baseQuery)->where(function (Builder $q) {
            $q->whereNull('IranCode')->orWhere('IranCode', '=', '');
        })->count();

        return collect([
            [
                'key' => '',
                'label' => __('app.warehouse_stats_in_stock_total'),
                'value' => $total,
                'icon' => 'boxes',
                'frame' => 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-400 dark:hover:border-zinc-500',
                'selected' => 'ring-2 ring-zinc-500 ring-offset-2 ring-offset-white dark:ring-offset-zinc-800',
                'icon_bg' => 'bg-zinc-100 dark:bg-zinc-900/30',
                'icon_text' => 'text-zinc-600 dark:text-zinc-400',
            ],
            [
                'key' => 'without_image',
                'label' => __('app.without_image'),
                'value' => $withoutImage,
                'icon' => 'image-off',
                'frame' => 'border-zinc-200 dark:border-zinc-700 hover:border-amber-400',
                'selected' => 'ring-2 ring-amber-500 ring-offset-2 ring-offset-white dark:ring-offset-zinc-800',
                'icon_bg' => 'bg-amber-100 dark:bg-amber-900/30',
                'icon_text' => 'text-amber-700 dark:text-amber-400',
            ],
            [
                'key' => 'without_irancode',
                'label' => __('app.without_irancode'),
                'value' => $withoutIranCode,
                'icon' => 'shield-alert',
                'frame' => 'border-zinc-200 dark:border-zinc-700 hover:border-rose-400',
                'selected' => 'ring-2 ring-rose-500 ring-offset-2 ring-offset-white dark:ring-offset-zinc-800',
                'icon_bg' => 'bg-rose-100 dark:bg-rose-900/30',
                'icon_text' => 'text-rose-700 dark:text-rose-400',
            ],
        ]);
    }

    #[Computed]
    public function items()
    {
        $fiscalYearRef = (string) config('sepidar.FiscalYearRef');

        return Item::query()
            ->with([
                'grouping:GroupingID,Title',
                'image:ItemRef,Thumbnail',
            ])
            ->withSum([
                'stockSummaries as stock_quantity' => function ($query) use ($fiscalYearRef) {
                    $query->where('FiscalYearRef', $fiscalYearRef);
                },
            ], 'Quantity')
            ->tap(fn (Builder $query) => $this->applyCommonFilters($query, $fiscalYearRef))
            ->tap(fn (Builder $query) => $this->applySort($query, $fiscalYearRef))
            ->paginate(50);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->groupingFilter = '';
        $this->statusFilter = '';
        $this->stockFilter = '';
        $this->iranCodeFilter = '';
        $this->codePrefix = '';
        $this->sortBy = 'created_desc';
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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingIranCodeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCodePrefix(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    #[On('panels.warehouse.item.edit-site.saved')]
    public function refreshAfterSiteCodeUpdate(): void
    {
        unset($this->items, $this->stats);
    }

    #[On('panels.warehouse.item.upload-image.saved')]
    public function refreshAfterItemImageUpload(): void
    {
        unset($this->items, $this->stats);
    }
};
?>

<x-slot name="title">
    {{ __('app.items') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.items') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.warehouse_items_description') }}</flux:subheading>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach($this->stats as $stat)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $stat['key'] }}')"
                class="relative w-full text-start cursor-pointer rounded-xl border p-4 bg-white dark:bg-zinc-800 shadow-sm transition-colors {{ $stat['frame'] }} {{ $statusFilter === $stat['key'] ? $stat['selected'] : '' }}"
            >
                <flux:subheading size="sm" class="uppercase tracking-wider">{{ $stat['label'] }}</flux:subheading>
                <div class="flex items-end justify-between mt-2">
                    <flux:heading size="xl" class="leading-none">{{ number_format($stat['value']) }}</flux:heading>
                    <div class="p-2 rounded-lg {{ $stat['icon_bg'] }}">
                        <flux:icon icon="{{ $stat['icon'] }}" variant="micro" class="{{ $stat['icon_text'] }}" />
                    </div>
                </div>
            </button>
        @endforeach
    </div>

    <flux:card class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-3">
            <flux:input
                wire:model.live.debounce.400ms="search"
                placeholder="{{ __('app.search_in_warehouse_items') }}"
                icon="magnifying-glass"
            />

            <flux:input
                wire:model.live.debounce.400ms="codePrefix"
                placeholder="{{ __('app.warehouse_item_code_prefix') }}"
                icon="hash"
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
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            <flux:select wire:model.live="iranCodeFilter" searchable placeholder="{{ __('app.warehouse_irancode_filter') }}">
                <option value="">{{ __('app.warehouse_irancode_all') }}</option>
                <option value="has">{{ __('app.warehouse_irancode_has') }}</option>
                <option value="none">{{ __('app.warehouse_irancode_none') }}</option>
            </flux:select>

            <flux:select wire:model.live="sortBy" searchable placeholder="{{ __('app.sort_by') }}">
                <option value="created_desc">{{ __('app.sort_by_date_desc') }}</option>
                <option value="created_asc">{{ __('app.sort_by_date_asc') }}</option>
                <option value="title_asc">{{ __('app.sort_title_asc') }}</option>
                <option value="title_desc">{{ __('app.sort_title_desc') }}</option>
                <option value="code_asc">{{ __('app.sort_code_asc') }}</option>
                <option value="code_desc">{{ __('app.sort_code_desc') }}</option>
                <option value="stock_desc">{{ __('app.sort_stock_desc') }}</option>
                <option value="stock_asc">{{ __('app.sort_stock_asc') }}</option>
                <option value="without_image_first">{{ __('app.sort_without_image_first') }}</option>
            </flux:select>

            <div class="md:col-span-2 flex items-end">
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
            <flux:table.column>{{ __('app.irancode') }}</flux:table.column>
            <flux:table.column>{{ __('app.created_at') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->items as $item)
                <flux:table.row :key="$item->ItemID">
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
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
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">{{ $item->Code }}</flux:table.cell>
                    <flux:table.cell>{{ $item->Title }}</flux:table.cell>
                    <flux:table.cell>{{ $item->grouping->Title ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ number_format((float) $item->stock_quantity, 2) }}</flux:table.cell>

                    <flux:table.cell>
                        @if($item->IranCode)
                            {{ $item->IranCode }}
                        @else
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge color="rose">{{ __('app.without_irancode') }}</flux:badge>
                                <flux:tooltip content="{{ __('app.irancode_not_set') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="sky"
                                        icon="pencil"
                                        icon:variant="outline"
                                        wire:click="$dispatch('panels.warehouse.item.edit-site.assign-data', { id: {{ $item->ItemID }} })"
                                    />
                                </flux:tooltip>
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        @if($item->CreationDate)
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($item->CreationDate)->format('%Y-%m-%d') }}
                        @else
                            -
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <livewire:panels.warehouse.item.edit-site />
    <livewire:panels.warehouse.item.upload-image />
</div>
