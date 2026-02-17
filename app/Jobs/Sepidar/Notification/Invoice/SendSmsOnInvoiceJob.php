<?php

namespace App\Jobs\Sepidar\Notification\Invoice;

use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsOnInvoiceJob implements ShouldQueue
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
        //
    }
}
