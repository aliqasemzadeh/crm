<?php

namespace App\Jobs\Sepidar\Notification;

use App\Models\Sepidar\SLS\InvoiceItem;
use App\Models\Sepidar\INV\Item;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InvoiceItemNotificationJob implements ShouldQueue
{
    use Queueable;
    public Item $item;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $itemId)
    {
        $this->item = Item::findOrFail($itemId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $title = $this->item->Title ?? $this->item->Title ??  $this->item->Code  ?? '---';
        $message = __('app.invoice_item_zero_stock', ['title' => $title]);
        \App\Jobs\Notification\BaleSendMessageJob::dispatch($message);
    }
}
