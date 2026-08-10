<?php

namespace App\Livewire\Panels\Sale\ItemPrice;

use App\Models\Sepidar\INV\Item;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

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
        Cache::forget("sale_items_by_grouping_v2_{$this->groupingId}");
        unset($this->items);
    }

    public function mount($groupingId): void
    {
        $this->groupingId = $groupingId;
    }

    public function toggleItem(int $itemId, bool $checked): void
    {
        $this->dispatch('panels.sale.item-price.selection.toggle', itemId: $itemId, checked: $checked);
    }

    #[Computed]
    public function items()
    {
        $groupingId = $this->groupingId;

        if (!blank($this->search)) {
            return Item::query()
                ->where('CodingGroupRef', $groupingId)
                ->where('Title', 'like', '%' . $this->search . '%')
                ->get();
        }

        $cacheKey = "sale_items_by_grouping_v2_{$groupingId}";
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
        return view('livewire.panels.sale.item-price.items');
    }
}
