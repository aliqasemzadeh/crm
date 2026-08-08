<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Accounting\PriceNote\ItemPriceFetcher;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class FetcherCard extends Component
{
    public $itemId;

    protected array $supportedFetchers = [
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

    #[Computed]
    public function fetchers()
    {
        $cacheKey = 'item-fetchers-'.$this->itemId;

        $cached = Cache::get($cacheKey);

        if ($this->isValidFetchersCollection($cached)) {
            return $cached;
        }

        if ($cached !== null) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () {
            return ItemPriceFetcher::where('item_id', $this->itemId)->get();
        });
    }

    protected function isValidFetchersCollection(mixed $value): bool
    {
        if (! $value instanceof Collection) {
            return false;
        }

        if ($value->isEmpty()) {
            return true;
        }

        return $value->every(fn ($item) => $item instanceof ItemPriceFetcher);
    }

    protected function bustFetchersCache(): void
    {
        Cache::forget('item-fetchers-'.$this->itemId);
        unset($this->fetchers);
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

    public function delete($id)
    {
        ItemPriceFetcher::find($id)?->delete();
        $this->bustFetchersCache();
        Flux::toast(__('app.deleted_successfully', ['name' => __('app.fetcher')]));
    }

    public function placeholder()
    {
        return <<<'HTML'
            <div class="mt-2"><flux:icon.loading class="size-4" /></div>
        HTML;
    }

    public function render()
    {
        return view('livewire.panels.accounting.price-note.fetcher-card');
    }
}
