<?php

namespace App\Livewire\Panel\Administrator\Dashboard;

use App\Models\Sepidar\INV\InventoryReceiptItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Computed(cache: true)]
    public function inventory()
    {
        $balance = 0;
        $items = \App\Models\Sepidar\INV\ItemStockSummary::query()
            ->where('FiscalYearRef',config('sepidar.FiscalYearRef'))
            ->get();
        foreach ($items as $item) {
            $receipt = InventoryReceiptItem::query()
                ->where('ItemRef', $item->ItemRef)
                ->latest('InventoryReceiptItemID')
                ->first();
            if ($receipt) {
                $balance += $receipt->Fee * $item->Quantity;
            }
        }
        return $balance;
    }

    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        $this->authorize('administrator_dashboard_index');
        return view('livewire.panel.administrator.dashboard.index');
    }
}
