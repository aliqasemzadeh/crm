<?php

namespace App\Livewire\Panels\Accounting\PriceNote;

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\Item;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class Items extends Component
{
    public string $search = '';
    public Grouping $grouping;

    #[On('panels.accounting.price-note.items.refresh')]
    public function refresh(): void
    {
        Cache::forget("items_by_grouping_{$this->grouping->GroupingID}");
        $this->render();
    }

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
        return Cache::remember($cacheKey, now()->addMinutes(8000), function () use ($groupingId) {
            return Item::query()
                ->where('CodingGroupRef', $groupingId)
                ->get();
        });
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div>
            <flux:icon.loading />
        </div>
        HTML;
    }

    public function render()
    {
        return view('livewire.panels.accounting.price-note.items');
    }
}
