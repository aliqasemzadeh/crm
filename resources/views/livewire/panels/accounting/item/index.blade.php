<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('app.items') }}</flux:heading>
    </div>

    <div class="space-y-4">
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="{{ __('app.search_placeholder') }}" />

        <flux:table :paginate="$this->items">
            <flux:table.columns>
                <flux:table.column>{{ __('app.image') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Code'" :direction="$sortDirection" wire:click="sort('Code')">{{ __('app.code') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'Title'" :direction="$sortDirection" wire:click="sort('Title')">{{ __('app.title') }}</flux:table.column>
                <flux:table.column>{{ __('app.grouping') }}</flux:table.column>
                <flux:table.column>{{ __('app.stock') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'CreationDate'" :direction="$sortDirection" wire:click="sort('CreationDate')">{{ __('app.created_at') }}</flux:table.column>
                <flux:table.column>{{ __('app.creator') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->items as $item)
                    <flux:table.row :key="$item->ItemID">
                        <flux:table.cell>
                            @if($item->image)
                                <img src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}" class="w-10 h-10 rounded shadow-sm">
                            @else
                                <div class="w-10 h-10 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center">
                                    <flux:icon name="photo" class="text-zinc-400" />
                                </div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">{{ $item->Code }}</flux:table.cell>

                        <flux:table.cell>{{ $item->Title }}</flux:table.cell>

                        <flux:table.cell>{{ $item->grouping->Title ?? '-' }}</flux:table.cell>

                        <flux:table.cell>
                            @if($lastStockSummary = \App\Models\Sepidar\INV\ItemStockSummary::where('ItemRef', $item->ItemID)->where('FiscalYearRef', config('sepidar.FiscalYearRef'))->first())
                                {{ number_format($lastStockSummary->Quantity) }}
                            @else
                                0
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromDateTime($item->CreationDate)->format('%Y-%m-%d') }}</flux:table.cell>

                        <flux:table.cell>{{ $item->creator->Name ?? $item->creator->UserName ?? '-' }}</flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button variant="ghost" size="sm" icon="file-text" wire:click="$dispatch('panels.accounting.grouping.item.invoice.assign-data', { id: '{{ $item->ItemID }}' })" tooltip="{{ __('app.invoices') }}"></flux:button>
                                <flux:button variant="ghost" size="sm" icon="clipboard-list" wire:click="$dispatch('panels.accounting.grouping.item.receipt.assign-data', { id: '{{ $item->ItemID }}' })" tooltip="{{ __('app.receipts') }}"></flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <livewire:panels.accounting.grouping.item.invoice />
    <livewire:panels.accounting.grouping.item.receipt />
    <livewire:panels.accounting.invoice.view />
    <livewire:panels.accounting.inventory-receipt.view />
</div>
