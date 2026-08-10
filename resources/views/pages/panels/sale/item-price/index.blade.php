<?php

use App\Models\Sepidar\GNR\Grouping;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.panels.sale')] class extends Component
{
    public $groupingId;

    /** @var array<int, int> */
    public array $selectedItemIds = [];

    public function mount($groupingId = 0): void
    {
        $this->authorize('sales_item_price_index');

        if (empty($groupingId)) {
            $this->groupingId = Grouping::query()
                ->whereNull('ParentGroupRef')
                ->where('EntityType', 'SG.Inventory.ItemManagement.Common.ItemCodingGroup')
                ->value('GroupingID') ?? Grouping::query()->value('GroupingID');
        } else {
            $this->groupingId = $groupingId;
        }
    }

    public function selectGrouping(int $groupingId): void
    {
        $this->groupingId = $groupingId;

        $this->js(
            'history.pushState({}, "", '.json_encode(
                route('panels.sale.item-price.index', ['groupingId' => $groupingId])
            ).')'
        );
    }

    #[On('panels.sale.item-price.selection.toggle')]
    public function toggleSelection(int $itemId, bool $checked): void
    {
        $itemId = (int) $itemId;

        if ($checked) {
            if (! in_array($itemId, $this->selectedItemIds, true)) {
                $this->selectedItemIds[] = $itemId;
            }

            return;
        }

        $this->selectedItemIds = array_values(array_filter(
            $this->selectedItemIds,
            fn ($id) => (int) $id !== $itemId
        ));
    }

    public function clearSelection(): void
    {
        $this->selectedItemIds = [];
    }

    public function goCreateCluster()
    {
        $this->authorize('sales_item_cluster_create');

        if ($this->selectedItemIds === []) {
            return null;
        }

        session([
            'sale.item_cluster.pending_item_refs' => array_values(array_unique(array_map(
                'intval',
                $this->selectedItemIds
            ))),
        ]);

        return $this->redirect(route('panels.sale.item-cluster.create'), navigate: true);
    }

    #[Computed]
    public function groupings()
    {
        return Cache::remember(
            'sale_item_price_root_groupings_v1',
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
};
?>

<x-slot name="title">
    {{ __('app.price_notes') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div>
            <flux:heading size="xl" level="1">{{ __('app.price_notes') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('app.sales_item_price_description') }}</flux:subheading>
        </div>
        <flux:separator variant="subtle" />
    </div>

    @can('sales_item_cluster_create')
        @if (count($selectedItemIds) > 0)
            <div class="sticky top-2 z-20 mb-4 rounded-xl border border-teal-200 bg-teal-50/95 p-3 shadow-sm dark:border-teal-800 dark:bg-teal-950/90 backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <flux:text class="font-medium">
                        {{ __('app.selected_items_count', ['count' => count($selectedItemIds)]) }}
                    </flux:text>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button variant="ghost" wire:click="clearSelection">
                            {{ __('app.clear_selection') }}
                        </flux:button>
                        <flux:button variant="primary" color="teal" icon="layers" wire:click="goCreateCluster">
                            {{ __('app.add_to_cluster') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    <livewire:panels.sale.item-price.fetchers />
    <livewire:panels.sale.item-price.edit-site />
    <livewire:panels.sale.item.upload-image />

    <div wire:key="sale-item-price-groupings-{{ $groupingId }}" class="flex overflow-x-auto md:grid md:grid-cols-6 gap-4 pb-2 scrollbar-hide">
        @foreach($this->groupings as $groupingItem)
            <flux:button
                variant="primary"
                :color="$groupingItem['GroupingID'] == $groupingId ? 'green' : ''"
                class="w-full shrink-0 md:shrink"
                wire:click="selectGrouping({{ $groupingItem['GroupingID'] }})"
            >
                {{ $groupingItem['Title'] }}
            </flux:button>
        @endforeach
    </div>

    <div class="mt-4" wire:key="sale-item-price-content-{{ $groupingId }}">
        @if(\App\Models\Sepidar\INV\Item::where('CodingGroupRef', $groupingId)->count() > 0)
            <livewire:panels.sale.item-price.items
                :grouping-id="$groupingId"
                :selected-item-ids="$selectedItemIds"
                :key="'sale-items-'.$groupingId"
                lazy
            />
        @else
            @php
                $saleGroupings = \Illuminate\Support\Facades\Cache::remember(
                    "sale_item_price_groupings_parent_v1_{$groupingId}",
                    now()->addHours(6),
                    fn () => \App\Models\Sepidar\GNR\Grouping::where('ParentGroupRef', $groupingId)
                        ->get(['GroupingID', 'Title', 'ParentGroupRef'])
                        ->map(fn ($g) => [
                            'GroupingID' => $g->GroupingID,
                            'Title' => $g->Title,
                            'ParentGroupRef' => $g->ParentGroupRef,
                        ])
                        ->all()
                );
            @endphp
            <flux:accordion>
                @foreach($saleGroupings as $sub_grouping)
                    @if(\App\Models\Sepidar\GNR\Grouping::where('ParentGroupRef', $sub_grouping['GroupingID'])->count() > 0)
                        @php
                            $saleSubGroupings = \Illuminate\Support\Facades\Cache::remember(
                                "sale_item_price_groupings_parent_v1_{$sub_grouping['GroupingID']}",
                                now()->addHours(6),
                                fn () => \App\Models\Sepidar\GNR\Grouping::where('ParentGroupRef', $sub_grouping['GroupingID'])
                                    ->get(['GroupingID', 'Title', 'ParentGroupRef'])
                                    ->map(fn ($g) => [
                                        'GroupingID' => $g->GroupingID,
                                        'Title' => $g->Title,
                                        'ParentGroupRef' => $g->ParentGroupRef,
                                    ])
                                    ->all()
                            );
                        @endphp
                        @foreach($saleSubGroupings as $sub_grouping_item)
                            <flux:accordion.item>
                                <flux:accordion.heading>
                                    {{ $sub_grouping_item['Title'] }}
                                </flux:accordion.heading>
                                <flux:accordion.content>
                                    <livewire:panels.sale.item-price.items
                                        :grouping-id="$sub_grouping_item['GroupingID']"
                                        :selected-item-ids="$selectedItemIds"
                                        :key="'sale-items-'.$sub_grouping_item['GroupingID']"
                                        lazy
                                    />
                                </flux:accordion.content>
                            </flux:accordion.item>
                        @endforeach
                    @else
                        <flux:accordion.item>
                            <flux:accordion.heading>{{ $sub_grouping['Title'] }}</flux:accordion.heading>
                            <flux:accordion.content>
                                <livewire:panels.sale.item-price.items
                                    :grouping-id="$sub_grouping['GroupingID']"
                                    :selected-item-ids="$selectedItemIds"
                                    :key="'sale-items-'.$sub_grouping['GroupingID']"
                                    lazy
                                />
                            </flux:accordion.content>
                        </flux:accordion.item>
                    @endif
                @endforeach
            </flux:accordion>
        @endif
    </div>
</div>
