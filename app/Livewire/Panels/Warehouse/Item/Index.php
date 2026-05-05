<?php

namespace App\Livewire\Panels\Warehouse\Item;

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $groupingFilter = '';

    public string $statusFilter = '';

    public string $sortBy = 'created_desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'groupingFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortBy' => ['except' => 'created_desc'],
        'page' => ['except' => 1],
    ];

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
        $baseQuery = Item::query()
            ->when($this->search, function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($search) {
                    $q->where('Code', 'like', $search)
                        ->orWhere('Title', 'like', $search)
                        ->orWhere('IranCode', 'like', $search);
                });
            })
            ->when($this->groupingFilter, function (Builder $query) {
                $query->where('CodingGroupRef', $this->groupingFilter);
            });

        $total = (clone $baseQuery)->count();
        $withoutImage = (clone $baseQuery)->doesntHave('image')->count();
        $withoutIranCode = (clone $baseQuery)->whereNull('IranCode')->count();

        return collect([
            [
                'key' => '',
                'label' => __('app.all_items'),
                'value' => $total,
                'color' => 'zinc',
                'icon' => 'boxes',
            ],
            [
                'key' => 'without_image',
                'label' => __('app.without_image'),
                'value' => $withoutImage,
                'color' => 'amber',
                'icon' => 'image-off',
            ],
            [
                'key' => 'without_irancode',
                'label' => __('app.without_irancode'),
                'value' => $withoutIranCode,
                'color' => 'rose',
                'icon' => 'shield-alert',
            ],
        ]);
    }

    #[Computed]
    public function items()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        $stockSubQuery = ItemStockSummary::query()
            ->selectRaw('ItemRef, SUM(Quantity) as quantity')
            ->where('FiscalYearRef', $fiscalYearRef)
            ->groupBy('ItemRef');

        return Item::query()
            ->leftJoinSub($stockSubQuery, 'stock_summary', function ($join) {
                $join->on('INV.Item.ItemID', '=', 'stock_summary.ItemRef');
            })
            ->with([
                'grouping:GroupingID,Title',
                'image:ItemRef,Thumbnail',
            ])
            ->select('INV.Item.*')
            ->selectRaw('COALESCE(stock_summary.quantity, 0) as stock_quantity')
            ->when($this->search, function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($search) {
                    $q->where('INV.Item.Code', 'like', $search)
                        ->orWhere('INV.Item.Title', 'like', $search)
                        ->orWhere('INV.Item.IranCode', 'like', $search);
                });
            })
            ->when($this->groupingFilter, function (Builder $query) {
                $query->where('INV.Item.CodingGroupRef', $this->groupingFilter);
            })
            ->when($this->statusFilter === 'without_image', function (Builder $query) {
                $query->doesntHave('image');
            })
            ->when($this->statusFilter === 'without_irancode', function (Builder $query) {
                $query->whereNull('INV.Item.IranCode');
            })
            ->tap(function (Builder $query) {
                match ($this->sortBy) {
                    'created_asc' => $query->orderBy('INV.Item.CreationDate', 'asc'),
                    'without_image_first' => $query
                        ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM [INV].[ItemImage] WHERE [INV].[ItemImage].[ItemRef] = [INV].[Item].[ItemID]) THEN 1 ELSE 0 END ASC')
                        ->orderBy('INV.Item.CreationDate', 'desc'),
                    default => $query->orderBy('INV.Item.CreationDate', 'desc'),
                };
            })
            ->paginate(20);
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

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.panels.warehouse')]
    public function render()
    {
        return view('livewire.panels.warehouse.item.index');
    }
}
