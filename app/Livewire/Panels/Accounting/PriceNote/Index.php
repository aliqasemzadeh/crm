<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Sepidar\GNR\Grouping;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public $groupingId;

    public function mount($groupingId = 0): void
    {
        if (empty($groupingId)) {
            $this->groupingId = Grouping::firstOrFail()->GroupingID;
        } else {
            $this->groupingId = $groupingId;
        }
    }

    public function selectGrouping(int $groupingId): void
    {
        $this->groupingId = $groupingId;

        $this->js(
            'history.pushState({}, "", '.json_encode(
                route('panels.accounting.price-note.index', ['groupingId' => $groupingId])
            ).')'
        );
    }

    #[Computed]
    public function groupings()
    {
        return Cache::remember(
            'price_note_root_groupings_v3',
            now()->addHours(6),
            fn () => Grouping::query()
                ->whereNull('ParentGroupRef')
                ->where('EntityType', 'SG.Inventory.ItemManagement.Common.ItemCodingGroup')
                ->get(['GroupingID', 'Title'])
                ->map(fn (Grouping $grouping) => [
                    'GroupingID' => $grouping->GroupingID,
                    'Title' => $grouping->Title,
                ])
                ->all()
        );
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.price-note.index');
    }
}
