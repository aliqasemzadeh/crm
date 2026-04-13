<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\SLS\PriceNoteItem;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ItemFee extends Component
{
    public $itemId;
    public $priceNoteItemId = 0;
    public $fee = 0;

    public function mount($itemId)
    {
        $this->itemId = $itemId;
        if ($this->priceNote) {
            $this->priceNoteItemId = $this->priceNote->PriceNoteItemID;
            $this->fee = $this->priceNote->Fee;
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
            'fee' => 'required|min:1',
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
                'Fee' => $fee,
                'CurrencyRef' => 1,
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
                    'Fee' => $fee,
                ]);
            }
        }

        $itemName = Item::where('ItemID', $this->itemId)->value('Title') ?? __('app.not_specified');

        Flux::toast(__('app.saved_successfully', ['name' => $itemName]));
    }

    public function render()
    {
        return view('livewire.panels.accounting.price-note.item-fee');
    }
}
