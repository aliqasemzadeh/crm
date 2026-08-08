<div>
    <livewire:panels.accounting.grouping.item.invoice />
    <livewire:panels.sale.item-price.fetchers />
    <livewire:panels.sale.item-price.edit-site />
    <livewire:panels.accounting.grouping.item.receipt />
    <livewire:panels.accounting.invoice.view />
    <livewire:panels.accounting.inventory-receipt.view />
    <livewire:panels.sale.item-price.invoices :grouping-id="$groupingId" :key="'invoices-'.$groupingId" />
    <livewire:panels.sale.item-price.receipts :grouping-id="$groupingId" :key="'receipts-'.$groupingId" />

    <div wire:key="price-note-groupings-{{ $groupingId }}" class="flex overflow-x-auto md:grid md:grid-cols-6 gap-4 pb-2 scrollbar-hide">
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

    <livewire:panels.sale.item-price.stats :grouping-id="$groupingId" :key="'stats-'.$groupingId" lazy />

    <div class="mt-4" wire:key="price-note-content-{{ $groupingId }}">

        @if(\App\Models\Sepidar\INV\Item::where('CodingGroupRef', $groupingId)->count() > 0)
            <livewire:panels.sale.item-price.items :grouping-id="$groupingId" :key="'items-'.$groupingId" />
        @else
            @php
                $priceNoteGroupings = \Illuminate\Support\Facades\Cache::remember(
                    "price_note_groupings_parent_v3_{$groupingId}",
                    now()->addHours(6),
                    fn () => \App\Models\Sepidar\GNR\Grouping::where(
                        'ParentGroupRef',
                        $groupingId
                    )->get(['GroupingID', 'Title', 'ParentGroupRef'])
                    ->map(fn ($g) => [
                        'GroupingID' => $g->GroupingID,
                        'Title' => $g->Title,
                        'ParentGroupRef' => $g->ParentGroupRef,
                    ])
                    ->all()
                );
            @endphp
            <flux:accordion>
                @foreach($priceNoteGroupings as $sub_grouping)
                    @if(\App\Models\Sepidar\GNR\Grouping::where('ParentGroupRef', $sub_grouping['GroupingID'])->count() > 0)
                        @php
                            $priceNoteSubGroupings = \Illuminate\Support\Facades\Cache::remember(
                                "price_note_groupings_parent_v3_{$sub_grouping['GroupingID']}",
                                now()->addHours(6),
                                fn () => \App\Models\Sepidar\GNR\Grouping::where(
                                    'ParentGroupRef',
                                    $sub_grouping['GroupingID']
                                )->get(['GroupingID', 'Title', 'ParentGroupRef'])
                                ->map(fn ($g) => [
                                    'GroupingID' => $g->GroupingID,
                                    'Title' => $g->Title,
                                    'ParentGroupRef' => $g->ParentGroupRef,
                                ])
                                ->all()
                            );
                        @endphp

                        @foreach($priceNoteSubGroupings as $sub_grouping_item)
                            <flux:accordion.item>
                                <flux:accordion.heading>
                                    {{ $sub_grouping_item['Title'] }} - {{ $sub_grouping_item['GroupingID'] }}
                                </flux:accordion.heading>
                                <flux:accordion.content>
                                    <livewire:panels.sale.item-price.items
                                        :grouping-id="$sub_grouping_item['GroupingID']"
                                        :key="'items-'.$sub_grouping_item['GroupingID']"
                                    />
                                </flux:accordion.content>
                            </flux:accordion.item>
                        @endforeach
                    @else
                        <flux:accordion.item>
                            <flux:accordion.heading>{{ $sub_grouping['Title'] }} - {{ $sub_grouping['GroupingID'] }}</flux:accordion.heading>
                            <flux:accordion.content>
                                <livewire:panels.sale.item-price.items :grouping-id="$sub_grouping['GroupingID']" :key="'items-'.$sub_grouping['GroupingID']" />
                            </flux:accordion.content>
                        </flux:accordion.item>
                    @endif
                @endforeach
            </flux:accordion>
        @endif
    </div>


</div>
