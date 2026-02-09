<?php

namespace App\Livewire\Panels\Accounting\Grouping\Item;

use App\Models\Sepidar\SLS\InvoiceItem;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Invoice extends Component
{
    public $ItemId;
    public $average = 0;
    public $summation = 0;
    public $quantity = 0;

    #[On('panels.accounting.grouping.item.invoice.update-data')]
    public function updateData($summation, $quantity)
    {
        $this->summation = $summation;
        $this->quantity = $quantity;
        $this->average = $summation / $quantity;
    }

    #[On('panels.accounting.grouping.item.invoice.assign-data')]
    public function assignData($id): void
    {
        $this->ItemId = $id;
        Flux::modal('panels.accounting.grouping.item.invoice.modal')->show();
    }

    #[Computed]
    public function sales()
    {
        return InvoiceItem::with(['invoice'])
            ->where('ItemRef', $this->ItemId)
            ->latest('InvoiceItemId')
            ->get();
    }

    public function render()
    {
        return view('livewire.panels.accounting.grouping.item.invoice');
    }
}
