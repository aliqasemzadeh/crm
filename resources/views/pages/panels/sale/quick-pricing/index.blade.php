<?php

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\Local\INV\Cluster;
use App\Models\Sepidar\SLS\PriceNoteItem;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

return new #[Layout('layouts.panels.sale')] class extends Component
{
    public ?int $clusterId = null;

    public function mount(): void
    {
        $this->authorize('sales_quick_pricing_index');
    }

    public function updatedClusterId(): void
    {
        unset($this->cluster, $this->rows);
    }

    public function saveAll(): void
    {
        $this->authorize('sales_item_fee');
        $this->dispatch('panels.sale.quick-pricing.save-all');
        Flux::toast(__('app.saved_successfully', ['name' => __('app.quick_pricing')]));
    }

    #[Computed]
    public function clusters()
    {
        return Cluster::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    #[Computed]
    public function cluster(): ?Cluster
    {
        if (! $this->clusterId) {
            return null;
        }

        return Cluster::query()->find($this->clusterId);
    }

    #[Computed]
    public function rows()
    {
        $cluster = $this->cluster;
        if (! $cluster) {
            return collect();
        }

        $refs = collect($cluster->item_refs ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($refs->isEmpty()) {
            return collect();
        }

        $items = Item::query()
            ->whereIn('ItemID', $refs->all())
            ->orderBy('Title')
            ->get(['ItemID', 'Title', 'Code']);

        $priceNotes = PriceNoteItem::query()
            ->whereIn('ItemRef', $refs->all())
            ->get(['PriceNoteItemID', 'ItemRef', 'Fee'])
            ->keyBy('ItemRef');

        return $items->map(function (Item $item) use ($priceNotes) {
            $note = $priceNotes->get($item->ItemID);

            return [
                'id' => (int) $item->ItemID,
                'title' => (string) $item->Title,
                'code' => (string) $item->Code,
                'fee' => (int) ($note?->Fee ?? 0),
            ];
        });
    }
};
?>

<x-slot name="title">
    {{ __('app.sales') }} — {{ __('app.quick_pricing') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.quick_pricing') }}</flux:heading>
                <flux:subheading size="lg">{{ __('app.quick_pricing_description') }}</flux:subheading>
            </div>

            <div class="w-full max-w-md">
                <flux:select
                    wire:model.live="clusterId"
                    searchable
                    label="{{ __('app.item_cluster') }}"
                    placeholder="{{ __('app.select_item_cluster') }}"
                >
                    @foreach ($this->clusters as $clusterOption)
                        <flux:select.option value="{{ $clusterOption->id }}">
                            {{ $clusterOption->title }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
        <flux:separator variant="subtle" class="mt-6" />
    </div>

    @if ($this->cluster)
        <div class="mb-4 flex items-center justify-between gap-3">
            <flux:text>
                {{ __('app.quick_pricing_items_count', ['count' => $this->rows->count()]) }}
            </flux:text>
            @can('sales_item_fee')
                <flux:button variant="primary" color="orange" icon="save" wire:click="saveAll">
                    {{ __('app.save_all') }}
                </flux:button>
            @endcan
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('app.code') }}</flux:table.column>
                <flux:table.column>{{ __('app.title') }}</flux:table.column>
                <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                <flux:table.column>{{ __('app.options') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->rows as $row)
                    <livewire:sale.quick-pricing.item-price
                        :item-id="$row['id']"
                        :title="$row['title']"
                        :code="$row['code']"
                        :initial-fee="$row['fee']"
                        :key="'quick-price-'.$clusterId.'-'.$row['id']"
                    />
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center text-zinc-500">
                            {{ __('app.no_results') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    @else
        <flux:card class="text-center text-zinc-500 py-10">
            {{ __('app.select_item_cluster_to_price') }}
        </flux:card>
    @endif
</div>
