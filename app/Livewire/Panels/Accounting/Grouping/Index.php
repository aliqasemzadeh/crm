<?php

namespace App\Livewire\Panels\Accounting\Grouping;

use App\Models\Sepidar\GNR\Grouping;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public Grouping $grouping;
    public function mount(int $groupingId = 1): void
    {
        $this->grouping = Grouping::where('id', $groupingId)->firstOrFail();
    }

    #[Computed(cache: true)]
    public function groupings()
    {
        return Grouping::query()
            ->where('ParentGroupRef', null)
            ->where('EntityType', 'SG.Inventory.ItemManagement.Common.ItemCodingGroup')
            ->get();
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.grouping.index');
    }
}
