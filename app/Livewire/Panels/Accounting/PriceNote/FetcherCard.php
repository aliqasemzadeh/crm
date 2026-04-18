<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class FetcherCard extends Component
{
    public $itemId;

    public $supportedFetchers = [
        'DigikalaPriceFetcher' => \App\Support\DigikalaPriceFetcher::class,
        'FafaitPriceFetcher' => \App\Support\FafaitPriceFetcher::class,
        'FaterPriceFetcher' => \App\Support\FaterPriceFetcher::class,
        'MarkaziPriceFetcher' => \App\Support\MarkaziPriceFetcher::class,
        'SetareganPriceFetcher' => \App\Support\SetareganPriceFetcher::class,
        'HadishPriceFetcher' => \App\Support\HadishPriceFetcher::class,
        'TechnolifePriceFetcher' => \App\Support\TechnolifePriceFetcher::class,
        'SnappShopPriceFetcher' => \App\Support\SnappShopPriceFetcher::class,
        'WooCommerce' => \App\Support\WooPriceFetcher::class,
    ];

    public function getFetchersProperty()
    {
        return \Illuminate\Support\Facades\Cache::remember('item-fetchers-'.$this->itemId, 3600, function () {
            return \App\Models\Accounting\PriceNote\ItemPriceFetcher::where('item_id', $this->itemId)->get();
        });
    }

    public function run($id)
    {
        $itemFetcher = \App\Models\Accounting\PriceNote\ItemPriceFetcher::find($id);
        $fetcherClass = $this->supportedFetchers[$itemFetcher->fetcher] ?? null;

        if ($fetcherClass) {
            try {
                $logger = \Illuminate\Support\Facades\Log::channel('single');
                $price = $fetcherClass::fetchPrice($itemFetcher->link, $logger);

                if ($price) {
                    $itemFetcher->update([
                        'price' => (string) $price,
                        'message' => null,
                    ]);
                } else {
                    $itemFetcher->update([
                        'message' => 'Could not fetch price',
                    ]);
                }
            } catch (\Exception $e) {
                $itemFetcher->update([
                    'message' => $e->getMessage(),
                ]);
            }
        }

        \Illuminate\Support\Facades\Cache::forget('item-fetchers-'.$this->itemId);
    }

    public function delete($id)
    {
        \App\Models\Accounting\PriceNote\ItemPriceFetcher::find($id)->delete();
        \Illuminate\Support\Facades\Cache::forget('item-fetchers-'.$this->itemId);
    }

    public function render()
    {
        return view('livewire.panels.accounting.price-note.fetcher-card', [
            'fetchers' => $this->fetchers,
        ]);
    }
}
