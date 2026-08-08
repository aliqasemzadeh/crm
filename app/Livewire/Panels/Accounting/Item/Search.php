<?php

namespace App\Livewire\Panels\Accounting\Item;

use App\Models\Sepidar\INV\Item;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Search extends Component
{
    public string $search = '';

    public bool $open = false;

    public function updatedSearch(): void
    {
        $this->open = strlen(trim($this->search)) >= 2;
    }

    public function clear(): void
    {
        $this->search = '';
        $this->open = false;
        unset($this->results);
    }

    public function close(): void
    {
        $this->open = false;
    }

    #[Computed]
    public function results()
    {
        $term = trim($this->search);

        if (strlen($term) < 2) {
            return collect();
        }

        $fiscalYearRef = config('sepidar.FiscalYearRef');

        return Item::query()
            ->with(['image', 'grouping.parent', 'product'])
            ->withSum([
                'stockSummaries as stock_quantity' => fn ($query) => $query->where('FiscalYearRef', $fiscalYearRef),
            ], 'Quantity')
            ->where(function ($query) use ($term) {
                $query->where('Title', 'like', '%'.$term.'%')
                    ->orWhere('Title_En', 'like', '%'.$term.'%')
                    ->orWhere('Code', 'like', '%'.$term.'%')
                    ->orWhere('IranCode', 'like', '%'.$term.'%');
            })
            ->orderBy('Title')
            ->limit(10)
            ->get()
            ->map(function (Item $item) {
                return [
                    'id' => $item->ItemID,
                    'title' => $item->Title,
                    'code' => $item->Code,
                    'iran_code' => $item->IranCode,
                    'main_grouping' => $item->mainGroupingTitle(),
                    'grouping' => $item->grouping?->Title,
                    'stock' => (float) ($item->stock_quantity ?? 0),
                    'last_purchase_price' => (float) $item->getLastPurchasePrice(),
                    'last_sale_price' => (float) $item->getLastSalePrice(),
                    'site_price' => $item->siteMinPriceRial(),
                    'site_url' => $item->siteUrl(),
                    'thumbnail' => $item->image?->Thumbnail,
                    'url' => route('panels.accounting.item.show', $item->ItemID),
                ];
            });
    }

    public function render()
    {
        return view('livewire.panels.accounting.item.search');
    }
}
