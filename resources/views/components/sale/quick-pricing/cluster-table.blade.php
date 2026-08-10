<?php

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\Local\INV\Cluster;
use App\Models\Sepidar\SLS\PriceNoteItem;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $clusterId;

    public string $clusterTitle = '';

    #[Computed]
    public function clusterMeta(): array
    {
        $cluster = Cluster::query()
            ->whereKey($this->clusterId)
            ->first(['id', 'available_item_refs', 'item_refs']);

        if (! $cluster) {
            return ['total' => 0, 'refs' => collect()];
        }

        return [
            'total' => count($cluster->item_refs ?? []),
            'refs' => collect($cluster->available_item_refs ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values(),
        ];
    }

    #[Computed]
    public function rows()
    {
        $refs = $this->clusterMeta['refs'];

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

    public function placeholder(array $params = [])
    {
        $title = e($params['clusterTitle'] ?? '');
        $loading = e(__('app.loading'));

        return <<<HTML
        <flux:card class="mb-4">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading size="lg">{$title}</flux:heading>
            </div>
            <div class="flex flex-col items-center justify-center gap-3 py-10">
                <flux:icon.loading class="size-8 text-zinc-400" />
                <flux:text class="text-zinc-500">{$loading}</flux:text>
            </div>
        </flux:card>
        HTML;
    }
};
?>

<flux:card class="mb-4" wire:key="quick-pricing-cluster-card-{{ $clusterId }}">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ $clusterTitle }}</flux:heading>
            <flux:text>
                @if ($this->clusterMeta['total'] > $this->rows->count())
                    {{ __('app.item_cluster_items_count', [
                        'available' => number_format($this->rows->count()),
                        'total' => number_format($this->clusterMeta['total']),
                    ]) }}
                @else
                    {{ __('app.quick_pricing_cluster_items_count', ['count' => $this->rows->count()]) }}
                @endif
            </flux:text>
        </div>
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
</flux:card>
