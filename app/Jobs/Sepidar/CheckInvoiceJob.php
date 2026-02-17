<?php

namespace App\Jobs\Sepidar;

use App\Jobs\Sepidar\Notification\InvoiceItemNotificationJob;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckInvoiceJob implements ShouldQueue
{
    use Queueable;
    public Invoice $invoice;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $invoiceId)
    {
        $this->invoice = Invoice::with(['items.item'])->find($invoiceId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \App\Jobs\Sepidar\Notification\Invoice\SendSmsOnInvoiceJob::dispatch($this->invoiceId);

        foreach ($this->invoice->items as $item) {
            $lastStockSummary = \App\Models\Sepidar\INV\ItemStockSummary::query()
                ->where('ItemRef', $item->ItemRef)
                ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
                ->first();
            if ($lastStockSummary) {
                if ($lastStockSummary->Quantity == 0) {
                    InvoiceItemNotificationJob::dispatch($item->ItemRef);
                }
            }
        }
    }
}
