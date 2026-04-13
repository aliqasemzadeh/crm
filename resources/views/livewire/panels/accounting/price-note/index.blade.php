<div>
    <livewire:panels.accounting.grouping.item.invoice />
    <livewire:panels.accounting.price-note.fetchers />
    <livewire:panels.accounting.grouping.item.receipt />
    <livewire:panels.accounting.invoice.view />
    <livewire:panels.accounting.inventory-receipt.view />

    <div class="flex overflow-x-auto md:grid md:grid-cols-6 gap-4 pb-2 scrollbar-hide">
        @foreach($this->groupings as $groupingItem)
            <flux:button
                variant="primary"
                :color="$groupingItem->GroupingID == $grouping->GroupingID ? 'green' : ''"
                class="w-full shrink-0 md:shrink"
                wire:navigate
                href="{{ route('panels.accounting.price-note.index', ['groupingId' => $groupingItem->GroupingID]) }}"
            >
                {{ $groupingItem->Title }}
            </flux:button>
        @endforeach
    </div>

    <div class="mt-4">
        @if(\App\Models\Sepidar\INV\Item::where('CodingGroupRef', $grouping->GroupingID)->count() > 0)
            <livewire:panels.accounting.price-note.items :grouping-id="$grouping->GroupingID" />
        @else
            @php
                $groupings = \Illuminate\Support\Facades\Cache::remember(
                    "groupings_parent_{$grouping->GroupingID}",
                    now()->addHours(6),
                    fn () => \App\Models\Sepidar\GNR\Grouping::where(
                        'ParentGroupRef',
                        $grouping->GroupingID
                    )->get()
                );
            @endphp
            <flux:accordion>
                @foreach($groupings as $sub_grouping)
                    @if(\App\Models\Sepidar\GNR\Grouping::where('ParentGroupRef', $sub_grouping->GroupingID)->count() > 0)
                        @php
                            $subGroupings = \Illuminate\Support\Facades\Cache::remember(
                                "groupings_parent_{$sub_grouping->GroupingID}",
                                now()->addHours(6),
                                fn () => \App\Models\Sepidar\GNR\Grouping::where(
                                    'ParentGroupRef',
                                    $sub_grouping->GroupingID
                                )->get()
                            );
                        @endphp

                        @foreach($subGroupings as $sub_grouping_item)
                            <flux:accordion.item>
                                <flux:accordion.heading>
                                    {{ $sub_grouping_item->Title }} - {{ $sub_grouping_item->GroupingID }}
                                </flux:accordion.heading>
                                <flux:accordion.content>
                                    <livewire:panels.accounting.price-note.items
                                        :grouping-id="$sub_grouping_item->GroupingID"
                                    />
                                </flux:accordion.content>
                            </flux:accordion.item>
                        @endforeach
                    @else
                        <flux:accordion.item>
                            <flux:accordion.heading>{{ $sub_grouping->Title }} - {{ $sub_grouping->GroupingID }}</flux:accordion.heading>
                            <flux:accordion.content>
                                <livewire:panels.accounting.price-note.items :grouping-id="$sub_grouping->GroupingID" />
                            </flux:accordion.content>
                        </flux:accordion.item>
                    @endif
                @endforeach
            </flux:accordion>
        @endif
    </div>


</div>
