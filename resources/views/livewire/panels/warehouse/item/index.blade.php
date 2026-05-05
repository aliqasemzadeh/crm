<x-slot name="title">
    {{ __('app.items') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.items') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.warehouse_items_description') }}</flux:subheading>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach($this->stats as $stat)
            <div
                wire:click="$set('statusFilter', '{{ $stat['key'] }}')"
                class="relative cursor-pointer rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800 shadow-sm hover:border-{{ $stat['color'] }}-500 transition-colors {{ $statusFilter === $stat['key'] ? 'ring-2 ring-'.$stat['color'].'-500' : '' }}"
            >
                <flux:subheading size="sm" class="uppercase tracking-wider">{{ $stat['label'] }}</flux:subheading>
                <div class="flex items-end justify-between mt-2">
                    <flux:heading size="xl" class="leading-none">{{ number_format($stat['value']) }}</flux:heading>
                    <div class="p-2 rounded-lg bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30">
                        <flux:icon icon="{{ $stat['icon'] }}" variant="micro" class="text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <flux:card class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <flux:input
                wire:model.live.debounce.400ms="search"
                placeholder="{{ __('app.search_in_warehouse_items') }}"
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="groupingFilter" searchable placeholder="{{ __('app.grouping') }}">
                <option value="">{{ __('app.all_groupings') }}</option>
                @foreach($this->groupings as $grouping)
                    <option value="{{ $grouping->GroupingID }}">{{ $grouping->Title }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="sortBy" placeholder="{{ __('app.sort_by') }}">
                <option value="created_desc">{{ __('app.sort_by_date_desc') }}</option>
                <option value="created_asc">{{ __('app.sort_by_date_asc') }}</option>
                <option value="without_image_first">{{ __('app.sort_without_image_first') }}</option>
            </flux:select>

            <flux:button
                variant="primary"
                color="zinc"
                wire:click="$set('statusFilter', '')"
            >
                {{ __('app.clear_filter') }}
            </flux:button>
        </div>
    </flux:card>

    <flux:table :paginate="$this->items">
        <flux:table.columns>
            <flux:table.column>{{ __('app.image') }}</flux:table.column>
            <flux:table.column>{{ __('app.code') }}</flux:table.column>
            <flux:table.column>{{ __('app.title') }}</flux:table.column>
            <flux:table.column>{{ __('app.grouping') }}</flux:table.column>
            <flux:table.column>{{ __('app.stock') }}</flux:table.column>
            <flux:table.column>{{ __('app.irancode') }}</flux:table.column>
            <flux:table.column>{{ __('app.created_at') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->items as $item)
                <flux:table.row :key="$item->ItemID">
                    <flux:table.cell>
                        @if($item->image?->Thumbnail)
                            <img
                                src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}"
                                alt="{{ $item->Title }}"
                                class="w-12 h-12 rounded-md object-cover border border-zinc-200 dark:border-zinc-700"
                            />
                        @else
                            <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-md flex items-center justify-center">
                                <flux:icon icon="image-off" class="text-zinc-400" />
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">{{ $item->Code }}</flux:table.cell>
                    <flux:table.cell>{{ $item->Title }}</flux:table.cell>
                    <flux:table.cell>{{ $item->grouping->Title ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ number_format((float) $item->stock_quantity, 2) }}</flux:table.cell>

                    <flux:table.cell>
                        @if($item->IranCode)
                            {{ $item->IranCode }}
                        @else
                            <flux:badge color="rose">{{ __('app.without_irancode') }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        @if($item->CreationDate)
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($item->CreationDate)->format('%Y-%m-%d') }}
                        @else
                            -
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
