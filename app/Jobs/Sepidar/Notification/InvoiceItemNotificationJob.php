<?php

namespace App\Jobs\Sepidar\Notification;

use App\Models\Sepidar\SLS\InvoiceItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InvoiceItemNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public InvoiceItem $invoiceItem)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $title = $this->invoiceItem->item?->Title ?? '---';
        $message = __('app.invoice_item_zero_stock', ['title' => $title]);

        \App\Jobs\Notification\BaleSendMessageJob::dispatch($message);
    }
}
