<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Accounting\PriceNote\ItemPriceFetcher;
use Livewire\Attributes\On;
use Livewire\Component;

class Fetchers extends Component
{
    public $itemId;
    public $fetchers = [];
    public $fetcher;
    public $link;

    public $supportedFetchers = [
        'DigikalaPriceFetcher' => \App\Support\DigikalaPriceFetcher::class,
        'FafaitPriceFetcher' => \App\Support\FafaitPriceFetcher::class,
        'FaterPriceFetcher' => \App\Support\FaterPriceFetcher::class,
        'MarkaziPriceFetcher' => \App\Support\MarkaziPriceFetcher::class,
        'SetareganPriceFetcher' => \App\Support\SetareganPriceFetcher::class,
        'HadishPriceFetcher' => \App\Support\HadishPriceFetcher::class,
        'TechnolifePriceFetcher' => \App\Support\TechnolifePriceFetcher::class,
        'WooCommerce' => \App\Support\WooPriceFetcher::class,
    ];

    #[On('panels.accounting.price-note.fetchers.assign-data')]
    public function assignData($id)
    {
        $this->itemId = $id;
        $this->loadFetchers();
        $this->js('$flux.modal(\'panels.accounting.price-note.fetchers.modal\').show()');
    }

    public function loadFetchers()
    {
        $this->fetchers = ItemPriceFetcher::where('item_id', $this->itemId)->get();
    }

    public function add()
    {
        $this->validate([
            'fetcher' => 'required',
            'link' => 'required|url',
        ]);

        ItemPriceFetcher::create([
            'item_id' => $this->itemId,
            'fetcher' => $this->fetcher,
            'link' => $this->link,
        ]);

        $this->reset(['fetcher', 'link']);
        $this->loadFetchers();
    }

    public function delete($id)
    {
        ItemPriceFetcher::find($id)->delete();
        $this->loadFetchers();
    }

    public function run($id)
    {
        $itemFetcher = ItemPriceFetcher::find($id);
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

        $this->loadFetchers();
    }

    public function render()
    {
        return view('livewire.panels.accounting.price-note.fetchers');
    }
}
