<div>
    <flux:table>
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="8" class="bg-white dark:bg-zinc-900">
                    <flux:input
                        size="sm"
                        class="m-3"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns sticky>
            <flux:table.column>{{ __('app.image') }}</flux:table.column>
            <flux:table.column>{{ __('app.action') }}</flux:table.column>
            <flux:table.column>{{ __('app.name') }}</flux:table.column>
            <flux:table.column>{{ __('app.balance') }}</flux:table.column>
            <flux:table.column>{{ __('app.last_purchase_price') }}</flux:table.column>
            <flux:table.column>{{ __('app.last_sale_price') }}</flux:table.column>
            <flux:table.column>{{ __('app.fee') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($this->items as $item)
                @if($lastStockSummary = \App\Models\Sepidar\INV\ItemStockSummary::where('ItemRef', $item->ItemID)->where('FiscalYearRef', config('sepidar.FiscalYearRef'))->first())

                        <flux:table.row class="odd:bg-zinc-50 even:bg-white dark:odd:bg-zinc-800/50 dark:even:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <flux:table.cell>
                                <livewire:panels.sale.item-price.item-image :itemId="$item->ItemID" :key="'item-image-'.$item->ItemID" />
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($item->IranCode)
                                    <flux:button size="xs" target="_blank" type="link" href="https://setaregan.co/Product/{{ $item->IranCode }}"  variant="filled" color="rose">{{ __('app.website') }}</flux:button>
                                    <flux:button size="xs" variant="primary" wire:click="$dispatch('panels.sale.item-price.edit-site.assign-data', { id: '{{ $item->ItemID }}' })">{{ __('app.website_edit') }}</flux:button>
                                @else
                                    <flux:button size="xs" variant="danger" wire:click="$dispatch('panels.sale.item-price.edit-site.assign-data', { id: '{{ $item->ItemID }}' })">{{ __('app.irancode_not_set') }}</flux:button>
                                @endif
                                    @can('sales_item_fetchers')
                                    <flux:button size="xs" variant="primary" color="yellow" wire:click="$dispatch('panels.sale.item-price.fetchers.assign-data', { id: '{{ $item->ItemID }}' })">{{ __('app.get_price') }}</flux:button>
                                    @endcan
                                        <flux:button size="xs" variant="primary" color="sky" wire:click="$dispatch('panels.accounting.grouping.item.invoice.assign-data', { id: '{{ $item->ItemID }}' })">{{ __('app.invoices') }}</flux:button>
                                <flux:button size="xs" variant="primary" color="green" wire:click="$dispatch('panels.accounting.grouping.item.receipt.assign-data', { id: '{{ $item->ItemID }}' })">{{ __('app.receipts') }}</flux:button>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $item->Title }}
                                @can('sales_item_fetchers')
                                <livewire:panels.sale.item-price.fetcher-card :itemId="$item->ItemID" :key="'item-fetchers-'.$item->ItemID" />
                                @endcan
                            </flux:table.cell>
                            <flux:table.cell>
                                @can('sales_item_stock_summary')
                                {{ number_format($lastStockSummary->Quantity) }}
                                @endcan
                            </flux:table.cell>
                            <flux:table.cell>
                                @can('sales_item_purchase_price')
                                {{ number_format($item->getLastPurchasePrice()) }}
                                @endcan
                            </flux:table.cell>
                            <flux:table.cell>
                                @can('sales_item_sale_price')
                                {{ number_format($item->getLastSalePrice()) }}
                                @endcan
                            </flux:table.cell>
                            <flux:table.cell>
                                @can('sales_item_fee')
                                <livewire:panels.sale.item-price.item-fee :itemId="$item->ItemID" />
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                @endif
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
