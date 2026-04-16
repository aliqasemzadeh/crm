<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

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

    public $siteFee = 0;
    public $siteStock = 0;
    public $productPriceId = 0;

    public $siteSaved = false;
    public $feeSaved = false;

    public function updated($propertyName)
    {
        if ($propertyName === 'siteFee' || $propertyName === 'siteStock') {
            $this->siteSaved = false;
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
            $productPrice = $item->productPrices()->first();
            if ($productPrice) {
                $this->productPriceId = $productPrice->Id;
                $this->siteFee = (int) $productPrice->Price * 10;
                $this->siteStock = (int) $productPrice->Quantity;
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

    public function saveSite()
    {
        if ($this->productPriceId == 0) {
            return;
        }

        $this->validate([
            'siteFee' => ['required', new ItemPriceNoteFeeRule($this->itemId)],
        ], [], [
            'siteFee' => __('app.site_price'),
        ]);

        $siteFee = (int) str_replace(',', '', $this->siteFee) / 10;

        $productPrice = ProductPrice::find($this->productPriceId);
        if ($productPrice) {
            $productPrice->update([
                'Price' => $siteFee,
                'Quantity' => $this->siteStock,
                'PriceChangeDate' => now(),
            ]);

            if ((int) $this->siteStock === 0) {
                $productPrice->product()->update([
                    'MinPrice' => null,
                ]);
            } else {
                $productPrice->product()->update([
                    'MinPrice' => $siteFee,
                ]);
            }

            $this->siteSaved = true;

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

    public function render()
    {
        return view('livewire.panels.accounting.price-note.item-fee');
    }
}
