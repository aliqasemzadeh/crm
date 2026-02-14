<?php

namespace App\Livewire\Panels\Accounting\InventoryReceipt;

use App\Models\Sepidar\INV\InventoryReceipt;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public InventoryReceipt $receipt;

    #[On('panels.accounting.inventory-receipt.view.assign-data')]
    public function assignData($id): void
    {
        $this->receipt = InventoryReceipt::with(['items', 'items.item', 'dl'])->findOrFail($id);
        Flux::modal('panels.accounting.inventory-receipt.view.modal')->show();
    }

    public function render()
    {
        return view('livewire.panels.accounting.inventory-receipt.view');
    }
}
