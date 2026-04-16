<?php

namespace App\Livewire\Panels\Administrator\Dashboard;

use App\Models\Sepidar\INV\InventoryReceiptItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public $fiscalYearRef;

    public function mount()
    {
        $this->fiscalYearRef = config('sepidar.FiscalYearRef');
    }

    public function reload()
    {
        unset($this->inventory);
        unset($this->unlinkedItemsCount);
        unset($this->totalItemsCount);
    }

    #[Computed(cache: true)]
    public function inventory()
    {
        $fiscalYearRef = $this->fiscalYearRef;

        $items = \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->get();

        $itemIds = $items->pluck('ItemRef')->unique();

        // Get latest fee for each item
        $latestFees = InventoryReceiptItem::query()
            ->whereIn('ItemRef', $itemIds)
            ->whereIn('InventoryReceiptItemID', function ($query) {
                $query->selectRaw('MAX(InventoryReceiptItemID)')
                    ->from('INV.InventoryReceiptItem')
                    ->groupBy('ItemRef');
            })
            ->pluck('Fee', 'ItemRef');

        $balance = 0;
        foreach ($items as $item) {
            $fee = $latestFees[$item->ItemRef] ?? 0;
            $balance += $fee * $item->Quantity;
        }

        return $balance;
    }

    #[Computed(cache: true)]
    public function unlinkedItemsCount()
    {
        return \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef', $this->fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->whereHas('item', function ($query) {
                $query->whereNull('IranCode');
            })
            ->count();
    }

    #[Computed(cache: true)]
    public function totalItemsCount()
    {
        return \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef', $this->fiscalYearRef)
            ->count();
    }

    #[Computed]
    public function fiscalYears()
    {
        return \App\Models\Sepidar\FMK\FiscalYear::all();
    }

    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        $this->authorize('administrator_dashboard_index');
        return view('livewire.panels.administrator.dashboard.index');
    }
}
