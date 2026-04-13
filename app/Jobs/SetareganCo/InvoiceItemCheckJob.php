<?php

namespace App\Jobs\SetareganCo;

use App\Models\Sepidar\INV\Item;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InvoiceItemCheckJob implements ShouldQueue
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
        $item = $this->item;

        if ($item->product) {
            $product = $item->product;

            // صفر کردن موجودی در ProductPrice
            foreach ($product->prices as $price) {
                $price->update([
                    'Quantity' => 0
                ]);
            }

            // برابر کردن MinPrice با NULL در Product
            $product->update([
                'MinPrice' => null
            ]);

            \Illuminate\Support\Facades\Log::info("موجودی سایت برای محصول با کد ایران {$item->IranCode} صفر شد و MinPrice برابر NULL قرار گرفت.");
        }
    }
}
