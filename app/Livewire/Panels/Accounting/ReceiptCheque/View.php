<?php

namespace App\Livewire\Panels\Accounting\ReceiptCheque;

use App\Models\Sepidar\RPA\ReceiptCheque;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public ReceiptCheque $cheque;

    #[On('panels.accounting.receipt-cheque.view.assign-data')]
    public function assignData($id): void
    {
        $this->cheque = ReceiptCheque::with(['dl'])->findOrFail($id);
        Flux::modal('panels.accounting.receipt-cheque.view.modal')->show();
    }

    public function render()
    {
        return view('livewire.panels.accounting.receipt-cheque.view');
    }
}
