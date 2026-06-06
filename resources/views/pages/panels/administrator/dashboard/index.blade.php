<?php

use App\Models\Sepidar\INV\InventoryReceiptItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    public $fiscalYearRef;

    public function mount()
    {
        $this->authorize('administrator_dashboard_index');
        $this->fiscalYearRef = config('sepidar.FiscalYearRef');
    }

    public function reload()
    {
        unset($this->inventory);
        unset($this->unlinkedItemsCount);
        unset($this->totalItemsCount);
        unset($this->itemsWithImageCount);
    }

    #[Computed(cache: true)]
    public function inventory()
    {
        $fiscalYearRef = $this->fiscalYearRef;

        $items = \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->whereHas('item', function ($query) {
                $query->where('CodingGroupRef', '!=', 584);
            })
            ->get();

        $itemIds = $items->pluck('ItemRef')->unique();

        // Get latest fee for each item
        $latestFees = InventoryReceiptItem::query()
            ->whereIn('ItemRef', $itemIds)
            ->whereIn('InventoryReceiptItemID', function ($query) {
                $query->selectRaw('MAX(InventoryReceiptItemID)')
                    ->from('INV.InventoryReceiptItem')
                    ->groupBy('ItemRef');
            })
            ->pluck('Fee', 'ItemRef');

        $balance = 0;
        foreach ($items as $item) {
            $fee = $latestFees[$item->ItemRef] ?? 0;
            $balance += $fee * $item->Quantity;
        }

        return $balance;
    }

    #[Computed(cache: true)]
    public function unlinkedItemsCount()
    {
        return \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef', $this->fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->whereHas('item', function ($query) {
                $query->whereNull('IranCode')->where('CodingGroupRef', '!=', 584);
            })
            ->count();
    }

    #[Computed(cache: true)]
    public function totalItemsCount()
    {
        return \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef', $this->fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->whereHas('item', function ($query) {
                $query->where('CodingGroupRef', '!=', 584);
            })
            ->count();
    }

    #[Computed(cache: true)]
    public function itemsWithImageCount()
    {
        return \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef', $this->fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->whereHas('item', function ($query) {
                $query->where('CodingGroupRef', '!=', 584);
            })
            ->whereHas('item.image')
            ->count();
    }

    #[Computed]
    public function fiscalYears()
    {
        return \App\Models\Sepidar\FMK\FiscalYear::all();
    }
};

?>

<x-slot name="title">
    {{ __('app.accounting') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.administrator') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.administrator_description') }}</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button wire:click="reload" icon="arrow-path" size="sm" variant="subtle">{{ __('app.reload') }}</flux:button>
                <flux:select wire:model.live="fiscalYearRef" class="w-48">
                    @foreach($this->fiscalYears as $year)
                        <flux:select.option value="{{ $year->FiscalYearID }}">{{ $year->Title }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
        <div class="relative rounded-lg px-6 py-4 bg-zinc-50 dark:bg-zinc-700">
            <flux:subheading>{{ __('app.inventory_rial_balance') }}</flux:subheading>
            <flux:heading size="xl" class="mb-2">{{ number_format($this->inventory) }}</flux:heading>
            <div class="absolute top-0 right-0 pr-2 pt-2">
                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
            </div>
        </div>

        <div class="relative rounded-lg px-6 py-4 bg-zinc-50 dark:bg-zinc-700">
            <flux:subheading>{{ __('app.total_items_count') }}</flux:subheading>
            <flux:heading size="xl" class="mb-2">{{ number_format($this->totalItemsCount) }}</flux:heading>
            <div class="absolute top-0 right-0 pr-2 pt-2">
                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
            </div>
        </div>

        <div class="relative rounded-lg px-6 py-4 bg-zinc-50 dark:bg-zinc-700">
            <flux:subheading>{{ __('app.unlinked_items_count') }}</flux:subheading>
            <flux:heading size="xl" class="mb-2">{{ number_format($this->unlinkedItemsCount) }}</flux:heading>
            <div class="absolute top-0 right-0 pr-2 pt-2">
                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
            </div>
        </div>

        <div class="relative rounded-lg px-6 py-4 bg-zinc-50 dark:bg-zinc-700">
            <flux:subheading>{{ __('app.items_with_image_count') }}</flux:subheading>
            <flux:heading size="xl" class="mb-2">{{ number_format($this->itemsWithImageCount) }}</flux:heading>
            <div class="absolute top-0 right-0 pr-2 pt-2">
                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
            </div>
        </div>
    </div>
</div>
