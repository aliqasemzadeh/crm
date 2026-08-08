<?php

use App\Models\Sepidar\INV\InventoryReceiptItem;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\SLS\InvoiceItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.sale')] class extends Component
{
    use WithPagination;

    public Item $item;

    public function mount(Item $item): void
    {
        $this->authorize('sales_item_view');

        $this->item = $item->load(['image', 'grouping', 'product', 'creator']);
    }

    #[On('panels.sale.item.view.site-price-updated')]
    public function refreshSitePrice(): void
    {
        $this->item->load('product');
        unset($this->sitePrice);
    }

    #[Computed]
    public function stock(): float
    {
        return (float) ItemStockSummary::query()
            ->where('ItemRef', $this->item->ItemID)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->sum('Quantity');
    }

    #[Computed]
    public function lastPurchasePrice(): float
    {
        return (float) $this->item->getLastPurchasePrice();
    }

    #[Computed]
    public function lastSalePrice(): float
    {
        return (float) $this->item->getLastSalePrice();
    }

    #[Computed]
    public function sitePrice(): ?int
    {
        return $this->item->siteMinPriceRial();
    }

    #[Computed]
    public function mainGrouping(): ?string
    {
        return $this->item->mainGroupingTitle();
    }

    #[Computed]
    public function receipts()
    {
        return InventoryReceiptItem::query()
            ->with(['receipt.dl'])
            ->where('ItemRef', $this->item->ItemID)
            ->latest('InventoryReceiptItemID')
            ->paginate(10, pageName: 'receiptsPage');
    }

    #[Computed]
    public function invoices()
    {
        return InvoiceItem::query()
            ->with(['invoice'])
            ->where('ItemRef', $this->item->ItemID)
            ->latest('InvoiceItemId')
            ->paginate(10, pageName: 'invoicesPage');
    }
};
?>

<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex gap-4">
            <div class="shrink-0">
                @if ($item->image?->Thumbnail)
                    <img
                        src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}"
                        alt="{{ $item->Title }}"
                        class="size-24 rounded-xl object-cover shadow-md sm:size-28"
                    >
                @elseif ($item->image)
                    <img
                        src="{{ route('item.image', $item->ItemID) }}"
                        alt="{{ $item->Title }}"
                        class="size-24 rounded-xl object-cover shadow-md sm:size-28"
                    >
                @else
                    <div class="flex size-24 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800 sm:size-28">
                        <flux:icon name="package" class="size-10 text-zinc-400" />
                    </div>
                @endif
            </div>

            <div class="min-w-0 space-y-2">
                <flux:heading size="xl" class="break-words">{{ $item->Title }}</flux:heading>
                <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                    <span>{{ __('app.code') }}: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $item->Code }}</span></span>
                    @if ($item->IranCode)
                        <span class="text-zinc-300 dark:text-zinc-600">|</span>
                        <span>{{ __('app.irancode') }}: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $item->IranCode }}</span></span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($this->mainGrouping)
                        <flux:badge color="indigo">{{ __('app.main_grouping') }}: {{ $this->mainGrouping }}</flux:badge>
                    @endif
                    @if ($item->grouping?->Title)
                        <flux:badge color="zinc">{{ __('app.grouping') }}: {{ $item->grouping->Title }}</flux:badge>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-shrink-0 flex-wrap gap-2">
            @can('sales_item_image')
                <flux:tooltip content="{{ __('app.warehouse_item_upload_image') }}">
                    <flux:button
                        size="xs"
                        variant="primary"
                        color="violet"
                        icon="image"
                        icon:variant="outline"
                        wire:click="$dispatch('panels.sale.item.upload-image.assign-data', { id: '{{ $item->ItemID }}' })"
                    />
                </flux:tooltip>
            @endcan
            @can('sales_item_site_edit')
                <flux:tooltip content="{{ __('app.website_edit') }}">
                    <flux:button
                        size="xs"
                        variant="primary"
                        color="rose"
                        icon="globe"
                        icon:variant="outline"
                        wire:click="$dispatch('panels.sale.item-price.edit-site.assign-data', { id: '{{ $item->ItemID }}' })"
                    />
                </flux:tooltip>
            @endcan
            @if ($item->siteUrl())
                <flux:button
                    variant="primary"
                    color="rose"
                    icon="external-link"
                    href="{{ $item->siteUrl() }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    {{ __('app.website') }}
                </flux:button>
            @endif
            <flux:button
                variant="ghost"
                icon="arrow-right"
                href="{{ route('panels.sale.item.index') }}"
                wire:navigate
            >
                {{ __('app.back') }}
            </flux:button>
        </div>
    </div>

    <livewire:panels.sale.item.upload-image />
    <livewire:panels.sale.item-price.edit-site />

    {{-- Price cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:card class="flex flex-col gap-1 bg-teal-50 p-4 dark:bg-teal-950/20">
            <flux:text class="font-bold text-teal-700 dark:text-teal-400">{{ __('app.stock') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stock) }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-sky-50 p-4 dark:bg-sky-950/20">
            <flux:text class="font-bold text-sky-700 dark:text-sky-400">{{ __('app.last_sale_price') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->lastSalePrice) }} <span class="text-sm font-normal">{{ __('app.rial') }}</span></flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-amber-50 p-4 dark:bg-amber-950/20">
            <flux:text class="font-bold text-amber-700 dark:text-amber-400">{{ __('app.last_purchase_price') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->lastPurchasePrice) }} <span class="text-sm font-normal">{{ __('app.rial') }}</span></flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-rose-50 p-4 dark:bg-rose-950/20">
            <flux:text class="font-bold text-rose-700 dark:text-rose-400">{{ __('app.site_price') }}</flux:text>
            <flux:heading size="lg">
                @if ($this->sitePrice !== null)
                    {{ number_format($this->sitePrice) }} <span class="text-sm font-normal">{{ __('app.rial') }}</span>
                @else
                    —
                @endif
            </flux:heading>
        </flux:card>
    </div>

    {{-- Details + site --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card class="space-y-4 p-4">
            <flux:heading size="lg">{{ __('app.item_details') }}</flux:heading>
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-zinc-500">{{ __('app.title') }}</dt>
                    <dd class="font-medium">{{ $item->Title }}</dd>
                </div>
                @if ($item->Title_En)
                    <div>
                        <dt class="text-zinc-500">{{ __('app.title_en') }}</dt>
                        <dd class="font-medium">{{ $item->Title_En }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-zinc-500">{{ __('app.code') }}</dt>
                    <dd class="font-medium">{{ $item->Code }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('app.irancode') }}</dt>
                    <dd class="font-medium">{{ $item->IranCode ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('app.main_grouping') }}</dt>
                    <dd class="font-medium">{{ $this->mainGrouping ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('app.grouping') }}</dt>
                    <dd class="font-medium">{{ $item->grouping?->Title ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('app.weight') }}</dt>
                    <dd class="font-medium">{{ $item->product?->Weight !== null ? number_format($item->product->Weight, 2) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('app.creator') }}</dt>
                    <dd class="font-medium">{{ $item->creator->Name ?? $item->creator->UserName ?? '—' }}</dd>
                </div>
                @if ($item->CreationDate)
                    <div>
                        <dt class="text-zinc-500">{{ __('app.created_at') }}</dt>
                        <dd class="font-medium">{{ Jalalian::fromDateTime($item->CreationDate)->format('%Y/%m/%d') }}</dd>
                    </div>
                @endif
                @if ($item->product?->PageLink)
                    <div class="sm:col-span-2">
                        <dt class="text-zinc-500">{{ __('app.site_page_link') }}</dt>
                        <dd class="font-medium break-all">{{ $item->product->PageLink }}</dd>
                    </div>
                @endif
            </dl>
        </flux:card>

        <flux:card class="space-y-4 p-4">
            <div class="flex items-center justify-between gap-2">
                <flux:heading size="lg">{{ __('app.website') }}</flux:heading>
                @if ($item->siteUrl())
                    <flux:button size="xs" variant="primary" color="rose" icon="external-link" href="{{ $item->siteUrl() }}" target="_blank" rel="noopener noreferrer">
                        {{ __('app.site_link') }}
                    </flux:button>
                @endif
            </div>

            @if (! $item->IranCode)
                <flux:callout variant="warning" icon="triangle-alert">
                    {{ __('app.irancode_not_set') }}
                </flux:callout>
            @elseif (! $item->product)
                <flux:callout variant="secondary" icon="info">
                    {{ __('app.item_site_product_missing') }}
                </flux:callout>
            @else
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-zinc-500">{{ __('app.site_price') }}</dt>
                        <dd class="font-medium">
                            {{ $this->sitePrice !== null ? number_format($this->sitePrice).' '.__('app.rial') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('app.weight') }}</dt>
                        <dd class="font-medium">{{ $item->product->Weight !== null ? number_format($item->product->Weight, 2) : '—' }}</dd>
                    </div>
                </dl>

                @canany(['sales_item_site_edit', 'sales_item_fee'])
                    <livewire:panels.sale.item-price.item-fee :itemId="$item->ItemID" :key="'item-fee-'.$item->ItemID" />
                @else
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('app.color') }}</flux:table.column>
                                <flux:table.column>{{ __('app.warranty') }}</flux:table.column>
                                <flux:table.column>{{ __('app.site_price') }}</flux:table.column>
                                <flux:table.column>{{ __('app.site_stock') }}</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse ($item->productPrices()->with(['color', 'guarantee'])->get() as $price)
                                    <flux:table.row :key="$price->Id">
                                        <flux:table.cell>
                                            <div class="flex items-center gap-2">
                                                @if ($price->color?->Code)
                                                    <span class="size-3 rounded-full border border-zinc-200" style="background-color: {{ $price->color->Code }}"></span>
                                                @endif
                                                {{ $price->color?->Title ?? '—' }}
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $price->guarantee?->Title ?? '—' }}</flux:table.cell>
                                        <flux:table.cell class="whitespace-nowrap">{{ number_format((int) $price->Price * 10) }}</flux:table.cell>
                                        <flux:table.cell>{{ number_format((int) $price->Quantity) }}</flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row>
                                        <flux:table.cell colspan="4" class="text-center text-zinc-500">{{ __('app.no_results') }}</flux:table.cell>
                                    </flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endcan
            @endif
        </flux:card>
    </div>

    {{-- Price fetchers --}}
    @can('sales_item_fetchers')
        <flux:card class="space-y-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:heading size="lg">{{ __('app.fetchers') }}</flux:heading>
                <flux:tooltip content="{{ __('app.get_price') }}">
                    <flux:button
                        size="xs"
                        variant="primary"
                        color="yellow"
                        icon="chart-candlestick"
                        icon:variant="outline"
                        wire:click="$dispatch('panels.sale.item-price.fetchers.assign-data', { id: {{ $item->ItemID }} })"
                    />
                </flux:tooltip>
            </div>
            <livewire:panels.sale.item-price.fetcher-card :itemId="$item->ItemID" :key="'item-fetchers-'.$item->ItemID" />
        </flux:card>
        <livewire:panels.sale.item-price.fetchers />
    @endcan

    {{-- Inventory receipts --}}
    <div>
        <flux:heading size="lg" class="mb-3">{{ __('app.inventory_receipts') }}</flux:heading>
        <div class="overflow-x-auto">
            <flux:table :paginate="$this->receipts">
                <flux:table.columns>
                    <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                    <flux:table.column>{{ __('app.customer') }}</flux:table.column>
                    <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                    <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('app.price') }}</flux:table.column>
                    <flux:table.column>{{ __('app.date') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->receipts as $buy)
                        <flux:table.row :key="$buy->InventoryReceiptItemID">
                            <flux:table.cell class="w-1 whitespace-nowrap">
                                @if ($buy->receipt)
                                    <flux:tooltip content="{{ __('app.view') }}">
                                        <flux:button
                                            size="xs"
                                            variant="primary"
                                            color="sky"
                                            icon="eye"
                                            icon:variant="outline"
                                            wire:click="$dispatch('panels.accounting.inventory-receipt.view.assign-data', { id: '{{ $buy->receipt->InventoryReceiptID }}' })"
                                        />
                                    </flux:tooltip>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $buy->receipt->dl->Title ?? $buy->receipt->DelivererDLRef ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ number_format($buy->Fee) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($buy->Quantity) }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ number_format($buy->Price) }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">
                                {{ $buy->receipt?->Date ? Jalalian::fromDateTime($buy->receipt->Date)->format('%Y/%m/%d') : '—' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500">{{ __('app.no_results') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- Invoices --}}
    <div>
        <flux:heading size="lg" class="mb-3">{{ __('app.invoices') }}</flux:heading>
        <div class="overflow-x-auto">
            <flux:table :paginate="$this->invoices">
                <flux:table.columns>
                    <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                    <flux:table.column>{{ __('app.customer') }}</flux:table.column>
                    <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                    <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('app.price') }}</flux:table.column>
                    <flux:table.column>{{ __('app.date') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->invoices as $sale)
                        <flux:table.row :key="$sale->InvoiceItemId">
                            <flux:table.cell class="w-1 whitespace-nowrap">
                                @if ($sale->invoice)
                                    <flux:tooltip content="{{ __('app.view') }}">
                                        <flux:button
                                            size="xs"
                                            variant="primary"
                                            color="sky"
                                            icon="eye"
                                            icon:variant="outline"
                                            wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { InvoiceId: '{{ $sale->invoice->InvoiceId }}' })"
                                        />
                                    </flux:tooltip>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $sale->invoice->CustomerRealName ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ number_format($sale->Fee) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($sale->Quantity) }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ number_format($sale->Price) }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">
                                {{ $sale->invoice?->CreationDate ? Jalalian::fromDateTime($sale->invoice->CreationDate)->format('%Y/%m/%d') : '—' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500">{{ __('app.no_results') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    <livewire:panels.accounting.invoice.view />
    <livewire:panels.accounting.inventory-receipt.view />
</div>
