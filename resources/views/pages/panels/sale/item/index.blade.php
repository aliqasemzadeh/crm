<?php

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Services\Sale\SaleTemporaryInvoice;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.panels.sale')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $groupingFilter = '';

    public string $sortBy = 'CreationDate';

    public string $sortDirection = 'desc';

    /** @var array<int, int> */
    public array $selectedItemIds = [];

    public function mount(): void
    {
        $this->authorize('sales_item_index');
    }

    public function sendToTemporaryInvoice(int $itemId, SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        if (! Item::query()->whereKey($itemId)->exists()) {
            Flux::toast(__('app.failed_to_add_to_temporary_invoice'), variant: 'danger');

            return;
        }

        $draft->add($itemId);
        $this->dispatch('panels.sale.temporary-invoice.updated');
        Flux::toast(__('app.added_to_temporary_invoice'));
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGroupingFilter(): void
    {
        $this->resetPage();
    }

    public function toggleItem(int $itemId): void
    {
        $itemId = (int) $itemId;
        $selected = array_map('intval', $this->selectedItemIds);

        if (in_array($itemId, $selected, true)) {
            $this->selectedItemIds = array_values(array_filter(
                $selected,
                fn ($id) => $id !== $itemId
            ));

            return;
        }

        $this->selectedItemIds = array_values(array_unique([...$selected, $itemId]));
    }

    public function togglePageSelection(): void
    {
        $pageIds = $this->items->getCollection()
            ->pluck('ItemID')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($pageIds === []) {
            return;
        }

        $selected = array_map('intval', $this->selectedItemIds);
        $allSelected = collect($pageIds)->every(fn ($id) => in_array($id, $selected, true));

        if ($allSelected) {
            $this->selectedItemIds = array_values(array_filter(
                $selected,
                fn ($id) => ! in_array($id, $pageIds, true)
            ));

            return;
        }

        $this->selectedItemIds = array_values(array_unique([...$selected, ...$pageIds]));
    }

    public function clearSelection(): void
    {
        $this->selectedItemIds = [];
    }

    public function goCreateCluster()
    {
        $this->authorize('sales_item_cluster_create');

        if ($this->selectedItemIds === []) {
            return null;
        }

        session([
            'sale.item_cluster.pending_item_refs' => array_values(array_unique(array_map(
                'intval',
                $this->selectedItemIds
            ))),
        ]);

        return $this->redirect(route('panels.sale.item-cluster.create'), navigate: true);
    }

    #[On('panels.sale.item.upload-image.saved')]
    public function refreshAfterImageUpload(): void
    {
        unset($this->items);
    }

    #[Computed]
    public function groupings()
    {
        $rows = Cache::remember('sale_item_root_groupings_filter_v1', now()->addHours(6), function () {
            return Grouping::query()
                ->whereNull('ParentGroupRef')
                ->where('EntityType', 'SG.Inventory.ItemManagement.Common.ItemCodingGroup')
                ->orderBy('Title')
                ->get(['GroupingID', 'Title'])
                ->map(fn (Grouping $grouping) => [
                    'GroupingID' => $grouping->GroupingID,
                    'Title' => $grouping->Title,
                ])
                ->all();
        });

        return collect($rows);
    }

    #[Computed]
    public function items()
    {
        return Item::query()
            ->with(['grouping', 'image', 'creator'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('Code', 'like', '%'.$this->search.'%')
                        ->orWhere('Title', 'like', '%'.$this->search.'%')
                        ->orWhere('IranCode', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->groupingFilter !== '', function ($query) {
                $grouping = Grouping::query()->find((int) $this->groupingFilter);
                if (! $grouping) {
                    return;
                }

                $query->whereIn('CodingGroupRef', $grouping->getAllChildrenIds());
            })
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(20);
    }
};
?>

<x-slot name="title">
    {{ __('app.items') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.items') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.sales_items_description') }}</flux:subheading>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    @can('sales_item_cluster_create')
        @if (count($selectedItemIds) > 0)
            <div class="sticky top-2 z-20 mb-4 rounded-xl border border-teal-200 bg-teal-50/95 p-3 shadow-sm dark:border-teal-800 dark:bg-teal-950/90 backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <flux:text class="font-medium">
                        {{ __('app.selected_items_count', ['count' => count($selectedItemIds)]) }}
                    </flux:text>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button variant="ghost" wire:click="clearSelection">
                            {{ __('app.clear_selection') }}
                        </flux:button>
                        <flux:button variant="primary" color="teal" icon="layers" wire:click="goCreateCluster">
                            {{ __('app.add_to_cluster') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    <livewire:panels.sale.item-price.edit-site />
    <livewire:panels.sale.item-price.fetchers />
    <livewire:panels.sale.item.upload-image />

    <flux:card class="mb-6">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <flux:input
                wire:model.live.debounce.400ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('app.search_placeholder') }}"
            />
            <flux:select
                wire:model.live="groupingFilter"
                searchable
                placeholder="{{ __('app.grouping') }}"
            >
                <flux:select.option value="">{{ __('app.all_groupings') }}</flux:select.option>
                @foreach ($this->groupings as $grouping)
                    <flux:select.option value="{{ $grouping['GroupingID'] }}">
                        {{ $grouping['Title'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:table :paginate="$this->items">
        <flux:table.columns>
            @can('sales_item_cluster_create')
                <flux:table.column class="w-10">
                    <flux:checkbox
                        size="sm"
                        wire:click="togglePageSelection"
                        :checked="$this->items->count() > 0 && collect($this->items->items())->every(fn ($item) => in_array((int) $item->ItemID, array_map('intval', $selectedItemIds), true))"
                    />
                </flux:table.column>
            @endcan
            <flux:table.column>{{ __('app.image') }}</flux:table.column>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Code'" :direction="$sortDirection" wire:click="sort('Code')">{{ __('app.code') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Title'" :direction="$sortDirection" wire:click="sort('Title')">{{ __('app.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.grouping') }}</flux:table.column>
            @can('sales_item_stock_summary')
                <flux:table.column>{{ __('app.stock') }}</flux:table.column>
            @endcan
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->items as $item)
                <flux:table.row :key="$item->ItemID">
                    @can('sales_item_cluster_create')
                        <flux:table.cell>
                            <flux:checkbox
                                size="sm"
                                :checked="in_array((int) $item->ItemID, array_map('intval', $selectedItemIds), true)"
                                wire:change="toggleItem({{ (int) $item->ItemID }})"
                            />
                        </flux:table.cell>
                    @endcan
                    <flux:table.cell>
                        @if($item->image)
                            <img src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}" class="w-10 h-10 rounded shadow-sm" alt="">
                        @else
                            <div class="w-10 h-10 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center">
                                <flux:icon name="photo" class="text-zinc-400" />
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="w-1 whitespace-nowrap">
                        <div class="flex items-center gap-1">
                            @can('sales_invoice_create')
                                <flux:tooltip content="{{ __('app.send_to_temporary_invoice') }}">
                                    <flux:button size="xs" variant="primary" color="teal" icon="file-text" icon:variant="outline" wire:click="sendToTemporaryInvoice({{ $item->ItemID }})" />
                                </flux:tooltip>
                            @endcan
                            @can('sales_item_view')
                                <flux:tooltip content="{{ __('app.view') }}">
                                    <flux:button size="xs" variant="primary" color="sky" icon="eye" icon:variant="outline" href="{{ route('panels.sale.item.view', $item->ItemID) }}" wire:navigate />
                                </flux:tooltip>
                            @endcan
                            @can('sales_item_image')
                                <flux:tooltip content="{{ __('app.warehouse_item_upload_image') }}">
                                    <flux:button size="xs" variant="primary" color="violet" icon="image" icon:variant="outline" wire:click="$dispatch('panels.sale.item.upload-image.assign-data', { id: '{{ $item->ItemID }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('sales_item_site_edit')
                                <flux:tooltip content="{{ __('app.website_edit') }}">
                                    <flux:button size="xs" variant="primary" color="rose" icon="globe-alt" icon:variant="outline" wire:click="$dispatch('panels.sale.item-price.edit-site.assign-data', { id: '{{ $item->ItemID }}' })" />
                                </flux:tooltip>
                            @endcan
                            @can('sales_item_fetchers')
                                <flux:tooltip content="{{ __('app.fetchers') }}">
                                    <flux:button size="xs" variant="primary" color="amber" icon="arrow-down-tray" icon:variant="outline" wire:click="$dispatch('panels.sale.item-price.fetchers.assign-data', { id: '{{ $item->ItemID }}' })" />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $item->Code }}</flux:table.cell>
                    <flux:table.cell>
                        <a href="{{ route('panels.sale.item.view', $item->ItemID) }}" wire:navigate class="font-medium text-sky-600 hover:underline dark:text-sky-400">
                            {{ $item->Title }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $item->grouping->Title ?? '-' }}</flux:table.cell>
                    @can('sales_item_stock_summary')
                        <flux:table.cell>
                            {{ number_format(
                                ItemStockSummary::query()
                                    ->where('ItemRef', $item->ItemID)
                                    ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
                                    ->sum('Quantity')
                            ) }}
                        </flux:table.cell>
                    @endcan
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
