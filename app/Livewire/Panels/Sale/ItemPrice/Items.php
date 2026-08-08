<?php

namespace App\Livewire\Panels\Sale\ItemPrice;

use App\Models\Sepidar\INV\Item;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class Items extends Component
{
    public string $search = '';

    public $groupingId;

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
