<?php

namespace App\Livewire\Panels\Accounting\Grouping;

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\Item;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class Items extends Component
{
    public string $search = '';
    public Grouping $grouping;

    public function mount(int $groupingId): void
    {
        $this->grouping = Grouping::where('GroupingID', $groupingId)->first();
    }

    #[Computed]
    public function items()
    {
        $groupingId = $this->grouping->GroupingID;

        if (!blank($this->search)) {
            return Item::query()
                ->where('CodingGroupRef', $groupingId)
                ->where('Title', 'like', '%' . $this->search . '%')
                ->get();
        }

        $cacheKey = "items_by_grouping_{$groupingId}";
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($groupingId) {
            return Item::query()
                ->where('CodingGroupRef', $groupingId)
                ->get();
        });
    }

    public function render()
    {
        return view('livewire.panels.accounting.grouping.items');
    }
}
