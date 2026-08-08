<?php

namespace App\Livewire\Panels\Accounting\Invoice;

use App\Jobs\Sepidar\Notification\Invoice\SendSmsOnInvoiceJob;
use App\Models\Sepidar\INV\InventoryReceiptItem;
use App\Models\Sepidar\SLS\Invoice;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public ?Invoice $invoice = null;
    #[On('panels.accounting.invoice.view.assign-data')]
    public function assignData($InvoiceId): void
    {
        $this->invoice = Invoice::with(['items', 'items.item', 'creator', 'modifier'])->findOrFail($InvoiceId);
        Flux::modal('panels.accounting.invoice.view.modal')->show();
    }

    public function sendToCustomer(): void
    {
        if (!isset($this->invoice)) {
            return;
        }
        SendSmsOnInvoiceJob::dispatch($this->invoice->InvoiceId);
        Flux::toast(__('app.invoice_sms_sent'));
    }

    public function render()
    {
        return view('livewire.panels.accounting.invoice.view');
    }
}
