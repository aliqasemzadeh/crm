<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\SLS\InvoiceItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public Grouping $grouping;

    public function mount(int $groupingId = 0): void
    {
        if($groupingId == 0) {
            $this->grouping = Grouping::firstOrFail();
        } else {
            $this->grouping = Grouping::where('GroupingId', $groupingId)->firstOrFail();
        }

    }

    #[Computed(cache: true)]
    public function groupings()
    {
        return Grouping::query()
            ->where('ParentGroupRef', null)
            ->where('EntityType', 'SG.Inventory.ItemManagement.Common.ItemCodingGroup')
            ->get();
    }

    #[Computed]
    public function salesStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "sales_stats_grouping_{$this->grouping->GroupingID}",
            now()->addHours(1),
            function () {
                $groupIds = $this->grouping->getAllChildrenIds();

                $stats = InvoiceItem::query()
                    ->whereHas('item', function ($query) use ($groupIds) {
                        $query->whereIn('CodingGroupRef', $groupIds);
                    })
                    ->selectRaw('SUM(Quantity) as total_quantity, SUM(Quantity * Fee) as total_amount')
                    ->first();

                return [
                    'total_quantity' => $stats->total_quantity ?? 0,
                    'total_amount' => $stats->total_amount ?? 0,
                ];
            }
        );
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.price-note.index');
    }
}
