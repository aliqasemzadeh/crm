<?php

namespace App\Livewire\Panels\Sale\ItemPrice;

use App\Models\Sepidar\INV\Item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

#[Lazy]
class Items extends Component
{
    public string $search = '';

    public $groupingId;

    /** @var array<int, int> */
    #[Reactive]
    public array $selectedItemIds = [];

    #[On('panels.sale.item-price.items.refresh')]
    public function refresh(): void
    {
        Cache::forget($this->cacheKey());
        unset($this->items);
    }

    public function mount($groupingId): void
    {
        $this->groupingId = $groupingId;
    }

    public function toggleItem(int $itemId): void
    {
        $checked = ! in_array($itemId, array_map('intval', $this->selectedItemIds), true);
        $this->dispatch('panels.sale.item-price.selection.toggle', itemId: $itemId, checked: $checked);
    }

    #[Computed]
    public function items(): Collection
    {
        $groupingId = (int) $this->groupingId;

        if (! blank($this->search)) {
            return Item::query()
                ->where('CodingGroupRef', $groupingId)
                ->where('Title', 'like', '%'.$this->search.'%')
                ->get();
        }

        // Database cache may not serialize Eloquent collections; cache IDs only.
        $itemIds = Cache::remember($this->cacheKey(), now()->addMinutes(8000), function () use ($groupingId) {
            return Item::query()
                ->where('CodingGroupRef', $groupingId)
                ->orderBy('Title')
                ->pluck('ItemID')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        });

        if (! is_array($itemIds) || $itemIds === []) {
            return collect();
        }

        $itemIds = array_values(array_filter(array_map('intval', $itemIds)));

        return Item::query()
            ->whereIn('ItemID', $itemIds)
            ->orderBy('Title')
            ->get();
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
        return view('livewire.panels.sale.item-price.items');
    }

    private function cacheKey(): string
    {
        return 'sale_items_by_grouping_v3_'.(int) $this->groupingId;
    }
}
