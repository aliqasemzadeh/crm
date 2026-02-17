<?php

namespace App\Livewire\Panels\Customer\Invoice;

use App\Models\Sepidar\SLS\Invoice;
use Livewire\Component;

class View extends Component
{
    public Invoice $invoice;

    public function mount($invoiceId)
    {
        $this->invoice = Invoice::with(['items.item', 'customer'])->findOrFail($invoiceId);
    }

    public function render()
    {
        return view('livewire.panels.customer.invoice.view')
            ->layout('layouts.panels.customer');
    }
}
