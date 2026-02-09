<?php

namespace App\Livewire\Panels\Accounting\Grouping\Item;

use App\Models\Sepidar\INV\InventoryReceiptItem;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Receipt extends Component
{
    public $ItemId;

    #[On('panels.accounting.grouping.item.receipt.assign-data')]
    public function assignData($id): void
    {
        $this->ItemId = $id;
        Flux::modal('panels.accounting.grouping.item.receipt.modal')->show();
    }

    #[Computed]
    public function buys()
    {
        return InventoryReceiptItem::with(['receipt', 'receipt.dl'])
            ->where('ItemRef', $this->ItemId)
            ->latest('InventoryReceiptItemId')
            ->get();
    }
    public function render()
    {
        return view('livewire.panels.accounting.grouping.item.receipt');
    }
}
