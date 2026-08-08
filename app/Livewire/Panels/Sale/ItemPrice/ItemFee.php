<?php

namespace App\Livewire\Panels\Sale\ItemPrice;

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\SLS\PriceNoteItem;
use App\Models\SetareganCo\ProductPrice;
use App\Rules\ItemPriceNoteFeeRule;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ItemFee extends Component
{
    public $itemId;
    public $priceNoteItemId = 0;
    public $fee = 0;

    public $prices = [];

    public $siteSaved = false;
    public $feeSaved = false;

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'prices.')) {
            $parts = explode('.', $propertyName);
            $index = $parts[1];
            $this->prices[$index]['saved'] = false;
        }

        if ($propertyName === 'fee') {
            $this->feeSaved = false;
        }
    }

    public function mount($itemId)
    {
        $this->itemId = $itemId;
        if ($this->priceNote) {
            $this->priceNoteItemId = $this->priceNote->PriceNoteItemID;
            $this->fee = $this->priceNote->Fee;
        }

        $this->loadSiteData();
    }

    public function loadSiteData()
    {
        $item = Item::find($this->itemId);
        if ($item && $item->product) {
            $productPrices = $item->productPrices()->with(['color', 'guarantee'])->get();
            foreach ($productPrices as $productPrice) {
                $this->prices[] = [
                    'id' => $productPrice->Id,
                    'fee' => (int) $productPrice->Price * 10,
                    'stock' => (int) $productPrice->Quantity,
                    'color' => $productPrice->color?->Title,
                    'color_code' => $productPrice->color?->Code,
                    'guarantee' => $productPrice->guarantee?->Title,
                    'saved' => false,
                ];
            }
        }
    }

    #[Computed]
    public function priceNote()
    {
        return PriceNoteItem::where('ItemRef', $this->itemId)->first();
    }

    public function save()
    {
        $this->authorize('sales_item_fee');

        $this->validate([
            'fee' => ['required', 'min:1', new ItemPriceNoteFeeRule($this->itemId)],
        ], [], [
            'fee' => __('app.fee'),
        ]);

        $fee = str_replace(',', '', $this->fee);

        if ($this->priceNoteItemId == 0) {
            $lastId = PriceNoteItem::max('PriceNoteItemID') ?? 0;
            $priceNoteItem = PriceNoteItem::create([
                'PriceNoteItemID' => $lastId + 1,
                'PriceNoteRef' => 1,
                'SaleTypeRef' => 1,
                'ItemRef' => $this->itemId,
                'UnitRef' => 1,
                'Fee' => (int) $fee,
                'CurrencyRef' => 1,
                'Discount' => 0,
                'CanChangeInvoiceFee' => 1,
                'CanChangeInvoiceDiscount' => 1,
                'AdditionRate' => 0,
                'EnforceFeeMargins' => 0,
            ]);
            $this->priceNoteItemId = $priceNoteItem->PriceNoteItemID;
        } else {
            $priceNoteItem = PriceNoteItem::find($this->priceNoteItemId);
            if ($priceNoteItem) {
                $priceNoteItem->update([
                    'Fee' => (int) $fee,
                    'Discount' => 0,
                ]);
            }
        }

        $this->feeSaved = true;

        $itemName = Item::where('ItemID', $this->itemId)->value('Title') ?? __('app.not_specified');

        Flux::toast(__('app.saved_successfully', ['name' => $itemName]));
    }

    public function saveSite($index)
    {
        $this->authorize('sales_item_site_edit');

        $priceData = $this->prices[$index];

        $this->validate([
            "prices.$index.fee" => ['required', new ItemPriceNoteFeeRule($this->itemId)],
        ], [], [
            "prices.$index.fee" => __('app.site_price'),
        ]);

        $siteFee = (int) str_replace(',', '', $priceData['fee']) / 10;

        $productPrice = ProductPrice::find($priceData['id']);
        if ($productPrice) {
            $productPrice->update([
                'Price' => $siteFee,
                'Quantity' => $priceData['stock'],
                'PriceChangeDate' => now(),
            ]);

            $minPrice = $productPrice->product->prices()->where('Quantity', '>', 0)->min('Price');

            $productPrice->product()->update([
                'MinPrice' => $minPrice,
            ]);

            $this->prices[$index]['saved'] = true;

            $this->dispatch('panels.sale.item.view.site-price-updated');

            Flux::toast(__('app.saved_successfully', ['name' => __('app.site_price')]));
        }
    }

    public function syncTitle()
    {
        $item = Item::find($this->itemId);
        if ($item && $item->product) {
            $item->product->update([
                'Name' => $item->Title,
            ]);

            Flux::toast(__('app.saved_successfully', ['name' => __('app.title')]));
        }
    }

    public function placeholder()
    {
        return <<<'HTML'
            <flux:icon.loading />
        HTML;
    }

    public function render()
    {
        return view('livewire.panels.sale.item-price.item-fee');
    }
}
