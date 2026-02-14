<?php

namespace App\Livewire\Panels\Accounting\PaymentCheque;

use App\Models\Sepidar\RPA\PaymentCheque;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public PaymentCheque $cheque;

    #[On('panels.accounting.payment-cheque.view.assign-data')]
    public function assignData($id): void
    {
        $this->cheque = PaymentCheque::with(['dl'])->findOrFail($id);
        Flux::modal('panels.accounting.payment-cheque.view.modal')->show();
    }

    public function render()
    {
        return view('livewire.panels.accounting.payment-cheque.view');
    }
}
