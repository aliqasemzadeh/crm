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
        $this->cheque = PaymentCheque::with(['dl', 'bankAccount.bankBranch.bank'])->findOrFail($id);
        $this->markPassed($this->cheque);
        Flux::modal('panels.accounting.payment-cheque.view.modal')->show();
    }

    public function render()
    {
        if (isset($this->cheque)) {
            $this->cheque->loadMissing(['dl', 'bankAccount.bankBranch.bank']);
            $this->markPassed($this->cheque);
        }

        return view('livewire.panels.accounting.payment-cheque.view');
    }

    private function markPassed(PaymentCheque $cheque): void
    {
        $cheque->setAttribute('is_passed', $cheque->bankingItems()->exists() ? 1 : 0);
    }
}
