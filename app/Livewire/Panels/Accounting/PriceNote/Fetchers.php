<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Accounting\PriceNote\ItemPriceFetcher;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Fetchers extends Component
{
    public $itemId;
    public $fetcher;
    public $link;

    public array $supportedFetchers = [
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

    #[On('panels.accounting.price-note.fetchers.assign-data')]
    public function assignData($id)
    {
        $this->itemId = $id;
        $this->reset(['fetcher', 'link']);
        unset($this->fetchers);
        $this->js('$flux.modal(\'panels.accounting.price-note.fetchers.modal\').show()');
    }

    #[Computed]
    public function fetchers()
    {
        if (! $this->itemId) {
            return collect();
        }

        return ItemPriceFetcher::where('item_id', $this->itemId)->get();
    }

    protected function bustFetchersCache(): void
    {
        Cache::forget('item-fetchers-'.$this->itemId);
        unset($this->fetchers);
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

        $this->bustFetchersCache();
        $this->reset(['fetcher', 'link']);
        Flux::toast(__('app.saved_successfully', ['name' => __('app.fetcher')]));
    }

    public function delete($id)
    {
        ItemPriceFetcher::find($id)?->delete();
        $this->bustFetchersCache();
        Flux::toast(__('app.deleted_successfully', ['name' => __('app.fetcher')]));
    }

    public function run($id)
    {
        $itemFetcher = ItemPriceFetcher::find($id);

        if (! $itemFetcher) {
            return;
        }

        $fetcherClass = $this->supportedFetchers[$itemFetcher->fetcher] ?? null;

        if ($fetcherClass) {
            try {
                $logger = Log::channel('single');
                $price = $fetcherClass::fetchPrice($itemFetcher->link, $logger);

                if ($price) {
                    $itemFetcher->update([
                        'price' => (string) $price,
                        'message' => null,
                    ]);
                    Flux::toast(__('app.saved_successfully', ['name' => __('app.fetcher')]));
                } else {
                    $itemFetcher->update([
                        'message' => 'Could not fetch price',
                    ]);
                    Flux::toast(text: 'Could not fetch price', variant: 'danger');
                }
            } catch (\Exception $e) {
                $itemFetcher->update([
                    'message' => $e->getMessage(),
                ]);
                Flux::toast(text: $e->getMessage(), variant: 'danger');
            }
        }

        $this->bustFetchersCache();
    }

    public function placeholder()
    {
        return <<<'HTML'
            <flux:icon.loading />
        HTML;
    }

    public function render()
    {
        return view('livewire.panels.accounting.price-note.fetchers');
    }
}
