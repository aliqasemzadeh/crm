<?php

namespace App\Jobs\Sepidar\Notification\Invoice;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\URL;

class SendSmsOnInvoiceJob implements ShouldQueue
{
    use Queueable;
    public Invoice $invoice;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $invoiceId)
    {
        $this->invoice = Invoice::with(['customer.phones'])->find($invoiceId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->invoice || !$this->invoice->customer) {
            return;
        }

        $link = URL::signedRoute('invoice.view', ['invoiceId' => $this->invoice->InvoiceId]);

        $message = __('app.invoice_sms_message', [
            'website_title' => __('app.website_title'),
            'number' => $this->invoice->Number,
            'link' => $link,
        ]);

        $phones = $this->invoice->customer->phones->where('Type', 2);

        foreach ($phones as $phone) {
            if ($phone->Phone) {
                SendSmsMessageJob::dispatch($phone->Phone, $message);
            }
        }
    }
}
