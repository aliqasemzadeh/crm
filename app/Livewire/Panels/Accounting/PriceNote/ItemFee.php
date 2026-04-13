<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\SLS\PriceNoteItem;
use Flux\Flux;
use Livewire\Component;

class ItemFee extends Component
{
    public $itemId;
    public $priceNoteItemId = 0;
    public $fee = 0;
    public function mount($itemId)
    {
        $this->itemId = $itemId;
        $priceNote = PriceNoteItem::where('ItemRef', $itemId)->first();
        if ($priceNote) {
            $this->priceNoteItemId = $priceNote->PriceNoteItemID;
            $this->fee = $priceNote->Fee;
        }
    }

    public function save()
    {
        if ($this->priceNoteItemId == 0) {
            $priceNoteItem = PriceNoteItem::create([
                'PriceNoteItemID' => 1,
                'PriceNoteRef' => 1,
                'SaleTypeRef' => 1,
                'ItemRef' => $this->itemId,
                'UnitRef' => 1,
                'Fee' => $this->fee,
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
                    'Fee' => $this->fee,
                ]);
            }
        }

        Flux::toast(__('app.saved_successfully'));
    }

    public function render()
    {
        return view('livewire.panels.accounting.price-note.item-fee');
    }
}
