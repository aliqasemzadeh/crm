<?php

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Services\Sale\SaleCart;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.sale')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'CreationDate';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('sales_item_index');
    }

    public function addToCart(int $itemId, SaleCart $cart): void
    {
        $this->authorize('sales_invoice_create');

        if (! Item::query()->whereKey($itemId)->exists()) {
            Flux::toast(__('app.failed_to_add_to_cart'), variant: 'danger');

            return;
        }

        $cart->add($itemId);
        $this->dispatch('panels.sale.cart.updated');
        Flux::toast(__('app.product_added_to_cart'));
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

    #[On('panels.sale.item.upload-image.saved')]
    public function refreshAfterImageUpload(): void
    {
        unset($this->items);
    }

    #[Computed]
    public function items()
    {
        return Item::query()
            ->with(['grouping', 'image', 'creator'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('Code', 'like', '%'.$this->search.'%')
                        ->orWhere('Title', 'like', '%'.$this->search.'%')
                        ->orWhere('IranCode', 'like', '%'.$this->search.'%');
                });
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

    <livewire:panels.sale.item-price.edit-site />
    <livewire:panels.sale.item-price.fetchers />
    <livewire:panels.sale.item.upload-image />

    <flux:card class="mb-6">
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="{{ __('app.search_placeholder') }}" />
    </flux:card>

    <flux:table :paginate="$this->items">
        <flux:table.columns>
            <flux:table.column>{{ __('app.image') }}</flux:table.column>
            <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Code'" :direction="$sortDirection" wire:click="sort('Code')">{{ __('app.code') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Title'" :direction="$sortDirection" wire:click="sort('Title')">{{ __('app.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.grouping') }}</flux:table.column>
            @can('sales_item_stock_summary')
                <flux:table.column>{{ __('app.stock') }}</flux:table.column>
            @endcan
            @can('sales_item_sale_price')
                <flux:table.column>{{ __('app.last_sale_price') }}</flux:table.column>
            @endcan
            @can('sales_item_purchase_price')
                <flux:table.column>{{ __('app.last_purchase_price') }}</flux:table.column>
            @endcan
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->items as $item)
                <flux:table.row :key="$item->ItemID">
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
                                <flux:tooltip content="{{ __('app.add_to_cart') }}">
                                    <flux:button size="xs" variant="primary" color="teal" icon="shopping-cart" icon:variant="outline" wire:click="addToCart({{ $item->ItemID }})" />
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
                    @can('sales_item_sale_price')
                        <flux:table.cell>{{ number_format($item->getLastSalePrice()) }}</flux:table.cell>
                    @endcan
                    @can('sales_item_purchase_price')
                        <flux:table.cell>{{ number_format($item->getLastPurchasePrice()) }}</flux:table.cell>
                    @endcan
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
