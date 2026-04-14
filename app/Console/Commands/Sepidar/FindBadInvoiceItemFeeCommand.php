<?php

namespace App\Console\Commands\Sepidar;

use Illuminate\Console\Command;

class FindBadInvoiceItemFeeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sepidar:find-bad-invoice-item-fee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find invoice items with fee less than 50% of last purchase price';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        \App\Models\Sepidar\SLS\InvoiceItem::query()
            ->whereHas('invoice', function ($query) use ($fiscalYearRef) {
                $query->where('FiscalYearRef', $fiscalYearRef);
            })
            ->with(['invoice.creator', 'item'])
            ->chunk(5000, function ($items) {
                foreach ($items as $item) {
                    $salePrice = $item->Fee;
                    $lastPurchasePrice = $item->item->getLastPurchasePrice();

                    if ($lastPurchasePrice > 0 && $salePrice < ($lastPurchasePrice * 0.5)) {
                        \Illuminate\Support\Facades\Log::warning(sprintf(
                            "Bad Invoice Item Fee - Invoice: %s, Creator: %s, Item: %s, Sale Price: %s, Last Purchase Price: %s",
                            $item->invoice->Number,
                            $item->invoice->creator->UserName ?? 'Unknown',
                            $item->item->Name,
                            number_format($salePrice),
                            number_format($lastPurchasePrice)
                        ));

                        $this->info(sprintf(
                            "Invoice: %s, Creator: %s, Item: %s, Sale Price: %s, Last Purchase Price: %s",
                            $item->invoice->Number,
                            $item->invoice->creator->UserName ?? 'Unknown',
                            $item->item->Name,
                            number_format($salePrice),
                            number_format($lastPurchasePrice)
                        ));
                    }
                }
            });
    }
}
