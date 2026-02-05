<?php

namespace App\Livewire\Panels\Administrator\Dashboard;

use App\Models\Sepidar\GNR\Grouping;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Groupings extends Component
{
    #[Computed(cache: true)]
    public function groupingss()
    {
        return Grouping::query()
            ->where('ParentGroupRef', null)
            ->where('EntityType', 'SG.Inventory.ItemManagement.Common.ItemCodingGroup')
            ->get();
    }

    public function render()
    {
        return view('livewire.panels.administrator.dashboard.groupings');
    }
}
