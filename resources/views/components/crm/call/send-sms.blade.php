<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\SLS\PriceNoteItem;
use Flux\Flux;

new class extends Component
{
    public $phone = '';
    public $message = '';
    public $search = '';
    public $selectedItemId = null;

    #[On('panels.crm.dashboard.index.send-sms')]
    public function show($phone)
    {
        $this->phone = $phone;
        $this->message = '';
        $this->search = '';
        $this->selectedItemId = null;
        $this->modal('send-sms-modal')->show();
    }

    #[Computed]
    public function items()
    {
        if (strlen($this->search) < 2) return [];

        return Item::where('ItemName', 'like', '%' . $this->search . '%')
            ->orWhere('ItemCode', 'like', '%' . $this->search . '%')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedItem()
    {
        if (!$this->selectedItemId) return null;

        $item = Item::find($this->selectedItemId);
        if (!$item) return null;

        $stock = ItemStockSummary::where('ItemRef', $item->ItemID)->sum('Quantity') ?? 0;
        $lastPurchasePrice = $item->getLastPurchasePrice();
        $lastSalePrice = $item->getLastSalePrice();

        return [
            'id' => $item->ItemID,
            'name' => $item->ItemName,
            'iran_code' => $item->IranCode,
            'stock' => $stock,
            'last_purchase_price' => $lastPurchasePrice,
            'last_sale_price' => $lastSalePrice,
        ];
    }

    public function addLink()
    {
        $item = $this->selectedItem();
        if ($item && $item['iran_code']) {
            $link = "https://setaregan.co/Product/" . $item['iran_code'];
            $this->message .= ($this->message ? "\n" : "") . $link;
        }
    }

    public function send()
    {
        $this->validate([
            'phone' => 'required',
            'message' => 'required',
        ]);

        // Logic for sending SMS would go here

        $this->modal('send-sms-modal')->close();
        Flux::toast(__('app.sms_sent_successfully'));
    }
};
?>

<div>
    <flux:modal name="send-sms-modal" flyout position="right" class="space-y-6 min-w-[400px]">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('app.send_sms') }} ({{ $phone }})</flux:heading>

            <flux:card class="space-y-4">
                <flux:select
                    wire:model.live="selectedItemId"
                    searchable
                    :placeholder="__('app.search_item')"
                    wire:key="item-search-select"
                >
                    <x-slot name="search">
                        <flux:select.search wire:model.live.debounce.500ms="search" />
                    </x-slot>

                    @foreach ($this->items as $item)
                        <flux:select.option value="{{ $item->ItemID }}">{{ $item->ItemName }} ({{ $item->ItemCode }})</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->selectedItem)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">{{ __('app.item_stock') }}:</span>
                            <span class="font-medium">{{ number_format($this->selectedItem['stock']) }}</span>
                        </div>
                        <div class="flex justify-between text-orange-600">
                            <span>{{ __('app.last_purchase_price') }}:</span>
                            <span class="font-medium">{{ number_format($this->selectedItem['last_purchase_price']) }} {{ __('app.rial') }}</span>
                        </div>
                        <div class="flex justify-between text-teal-600">
                            <span>{{ __('app.last_sale_price') }}:</span>
                            <span class="font-medium">{{ number_format($this->selectedItem['last_sale_price']) }} {{ __('app.rial') }}</span>
                        </div>

                        <flux:button variant="filled" color="teal" class="w-full mt-2" icon="plus" wire:click="addLink">
                            {{ __('app.add_product_link') }}
                        </flux:button>
                    </div>
                @endif
            </flux:card>

            <flux:textarea
                wire:model="message"
                :label="__('app.sms_message')"
                rows="8"
                class="w-full"
            />

            <div class="flex gap-2">
                <flux:button variant="primary" color="blue" class="w-full" wire:click="send">
                    {{ __('app.send_sms') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
