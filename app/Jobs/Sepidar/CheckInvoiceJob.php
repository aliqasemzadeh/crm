<?php

namespace App\Jobs\Sepidar;

use App\Jobs\Sepidar\Notification\Invoice\SendSmsOnInvoiceJob;
use App\Jobs\Sepidar\Notification\InvoiceItemNotificationJob;
use App\Jobs\SetareganCo\InvoiceItemCheckJob;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckInvoiceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $invoiceId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = Invoice::query()
            ->with(['items.item'])
            ->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        CreateInvoiceReviewJob::dispatch($this->invoiceId);

        SendSmsOnInvoiceJob::dispatch($this->invoiceId);

        $itemRefs = $invoice->items->pluck('ItemRef')->filter()->unique()->values();

        if ($itemRefs->isEmpty()) {
            return;
        }

        $zeroStockItemRefs = ItemStockSummary::query()
            ->whereIn('ItemRef', $itemRefs)
            ->where('FiscalYearRef', config('sepidar.FiscalYearRef'))
            ->get()
            ->filter(fn (ItemStockSummary $summary): bool => (float) ($summary->Quantity ?? 0) == 0)
            ->pluck('ItemRef')
            ->unique();

        foreach ($zeroStockItemRefs as $itemRef) {
            InvoiceItemNotificationJob::dispatch($itemRef);
            InvoiceItemCheckJob::dispatch($itemRef);
        }
    }
}
