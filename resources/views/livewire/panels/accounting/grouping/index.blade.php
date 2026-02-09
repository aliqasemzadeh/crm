<div>
    <livewire:panel.shop.sepidar.grouping.item.invoice />
    <livewire:panel.shop.sepidar.grouping.item.receipt />
    <livewire:panel.shop.sepidar.invoice.view />

    @foreach($this->groupings as $groupingItem)
        <flux:button variant="primary" wire:navigate href="{{ route('panels.accounting.grouping.index', ['groupingId' => $groupingItem->id]) }}">{{ $groupingItem->Title }}</flux:button>
    @endforeach
        <flux:spacer  />
        <flux:spacer />
        @if(\App\Models\Sepidar\INV\Item::where('CodingGroupRef', $grouping->GroupingID)->count() > 0)
            <livewire:panel.shop.sepidar.grouping.items :grouping-id="$grouping->GroupingID" />
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
                                    <livewire:panel.shop.sepidar.grouping.items
                                        :grouping-id="$sub_grouping_item->GroupingID"
                                    />
                                </flux:accordion.content>
                            </flux:accordion.item>
                        @endforeach
                    @else
                        <flux:accordion.item>
                            <flux:accordion.heading>{{ $sub_grouping->Title }} - {{ $sub_grouping->GroupingID }}</flux:accordion.heading>
                            <flux:accordion.content>
                                <livewire:panel.shop.sepidar.grouping.items :grouping-id="$sub_grouping->GroupingID" />
                            </flux:accordion.content>
                        </flux:accordion.item>
                    @endif
                @endforeach
            </flux:accordion>
        @endif

</div>
