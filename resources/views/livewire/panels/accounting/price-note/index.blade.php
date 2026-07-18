<div>
    <livewire:panels.accounting.grouping.item.invoice />
    <livewire:panels.accounting.price-note.fetchers />
    <livewire:panels.accounting.price-note.edit-site />
    <livewire:panels.accounting.grouping.item.receipt />
    <livewire:panels.accounting.invoice.view />
    <livewire:panels.accounting.inventory-receipt.view />
    <livewire:panels.accounting.price-note.invoices :grouping-id="$groupingId" />
    <livewire:panels.accounting.price-note.receipts :grouping-id="$groupingId" />

    <div class="flex overflow-x-auto md:grid md:grid-cols-6 gap-4 pb-2 scrollbar-hide">
        @foreach($this->groupings as $groupingItem)
            @if(is_object($groupingItem))
                <flux:button
                    variant="primary"
                    :color="$groupingItem->GroupingID == $groupingId ? 'green' : ''"
                    class="w-full shrink-0 md:shrink"
                    wire:navigate
                    href="{{ route('panels.accounting.price-note.index', ['groupingId' => $groupingItem->GroupingID]) }}"
                >
                    {{ $groupingItem->Title }}
                </flux:button>
            @endif
        @endforeach
    </div>

    <livewire:panels.accounting.price-note.stats :grouping-id="$groupingId" :key="'stats-'.$groupingId" lazy />

    <div class="mt-4">

        @if(\App\Models\Sepidar\INV\Item::where('CodingGroupRef', $groupingId)->count() > 0)
            <livewire:panels.accounting.price-note.items :grouping-id="$groupingId" />
        @else
            @php
                $priceNoteGroupings = \Illuminate\Support\Facades\Cache::remember(
                    "price_note_groupings_parent_{$groupingId}",
                    now()->addHours(6),
                    fn () => \App\Models\Sepidar\GNR\Grouping::where(
                        'ParentGroupRef',
                        $groupingId
                    )->get()
                );
            @endphp
            <flux:accordion>
                @foreach($priceNoteGroupings as $sub_grouping)
                    @if(is_object($sub_grouping))
                        @if(\App\Models\Sepidar\GNR\Grouping::where('ParentGroupRef', $sub_grouping->GroupingID)->count() > 0)
                            @php
                                $priceNoteSubGroupings = \Illuminate\Support\Facades\Cache::remember(
                                    "price_note_groupings_parent_{$sub_grouping->GroupingID}",
                                    now()->addHours(6),
                                    fn () => \App\Models\Sepidar\GNR\Grouping::where(
                                        'ParentGroupRef',
                                        $sub_grouping->GroupingID
                                    )->get()
                                );
                            @endphp

                            @foreach($priceNoteSubGroupings as $sub_grouping_item)
                                @if(is_object($sub_grouping_item))
                                    <flux:accordion.item>
                                        <flux:accordion.heading>
                                            {{ $sub_grouping_item->Title }} - {{ $sub_grouping_item->GroupingID }}
                                        </flux:accordion.heading>
                                        <flux:accordion.content>
                                            <livewire:panels.accounting.price-note.items
                                                :grouping-id="$sub_grouping_item->GroupingID"
                                                :key="'items-'.$sub_grouping_item->GroupingID"
                                            />
                                        </flux:accordion.content>
                                    </flux:accordion.item>
                                @endif
                            @endforeach
                        @else
                            <flux:accordion.item>
                                <flux:accordion.heading>{{ $sub_grouping->Title }} - {{ $sub_grouping->GroupingID }}</flux:accordion.heading>
                                <flux:accordion.content>
                                    <livewire:panels.accounting.price-note.items :grouping-id="$sub_grouping->GroupingID" :key="'items-'.$sub_grouping->GroupingID" />
                                </flux:accordion.content>
                            </flux:accordion.item>
                        @endif
                    @endif
                @endforeach
            </flux:accordion>
        @endif
    </div>


</div>
