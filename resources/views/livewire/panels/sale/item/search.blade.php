<?php

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
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

        $fiscalYearRef = (string) config('sepidar.FiscalYearRef');

        $stockSql = '(SELECT COALESCE(SUM(CAST(s.[Quantity] AS DECIMAL(18,4))), 0) FROM [INV].[ItemStockSummary] s WHERE s.[ItemRef] = [INV].[Item].[ItemID] AND s.[FiscalYearRef] = ?)';

        $items = Item::query()
            ->with(['image', 'grouping.parent', 'product'])
            ->where(function ($query) use ($term) {
                $query->where('Title', 'like', '%'.$term.'%')
                    ->orWhere('Title_En', 'like', '%'.$term.'%')
                    ->orWhere('Code', 'like', '%'.$term.'%')
                    ->orWhere('IranCode', 'like', '%'.$term.'%');
            })
            ->orderByRaw("CASE WHEN {$stockSql} > 0 THEN 0 ELSE 1 END", [$fiscalYearRef])
            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM [INV].[ItemImage] WHERE [INV].[ItemImage].[ItemRef] = [INV].[Item].[ItemID]) THEN 0 ELSE 1 END')
            ->orderByRaw("CASE WHEN [IranCode] IS NOT NULL AND LTRIM(RTRIM([IranCode])) <> '' THEN 0 ELSE 1 END")
            ->orderBy('Title')
            ->limit(10)
            ->get();

        $stocks = ItemStockSummary::query()
            ->whereIn('ItemRef', $items->pluck('ItemID'))
            ->where('FiscalYearRef', $fiscalYearRef)
            ->selectRaw('ItemRef, SUM(CAST(Quantity AS DECIMAL(18,4))) as total_quantity')
            ->groupBy('ItemRef')
            ->pluck('total_quantity', 'ItemRef');

        return $items->map(function (Item $item) use ($stocks) {
            return [
                'id' => $item->ItemID,
                'title' => $item->Title,
                'code' => $item->Code,
                'iran_code' => $item->IranCode,
                'main_grouping' => $item->mainGroupingTitle(),
                'grouping' => $item->grouping?->Title,
                'stock' => (float) ($stocks[$item->ItemID] ?? 0),
                'last_purchase_price' => (float) $item->getLastPurchasePrice(),
                'last_sale_price' => (float) $item->getLastSalePrice(),
                'site_price' => $item->siteMinPriceRial(),
                'site_url' => $item->siteUrl(),
                'thumbnail' => $item->image?->Thumbnail,
                'url' => route('panels.sale.item.view', $item->ItemID),
            ];
        });
    }
};
?>

@include('livewire.panels.accounting.item.search')
