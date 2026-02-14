<flux:modal name="panels.accounting.inventory-receipt.view.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.inventory_receipt_items') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.inventory_receipt_items_description') }}</flux:text>
        </div>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                @can('administrator_access')
                    <flux:table.column>{{ __('app.fee') }} (تتر)</flux:table.column>
                @endcan
                <flux:table.column>{{ __('app.price') }}</flux:table.column>
                @can('administrator_access')
                    <flux:table.column>{{ __('app.price') }} (تتر)</flux:table.column>
                @endcan
            </flux:table.columns>
            @if(isset($receipt))
                @php
                    $rate = \App\Models\CurrencyRate::getRate($receipt->Date) / 10;
                @endphp
                <flux:table.rows>
                    @foreach($receipt->items as $item)
                        <flux:table.row>
                            <flux:table.cell>
                                {{ $item->item->Title }}
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ number_format($item->Quantity) }}
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ number_format($item->Fee) }}
                            </flux:table.cell>

                            @can('administrator_access')
                                <flux:table.cell>
                                    {{ $rate > 0 ? number_format($item->Fee / $rate, 2) : '-' }}
                                </flux:table.cell>
                            @endcan

                            <flux:table.cell>
                                {{ number_format($item->Price) }}
                            </flux:table.cell>

                            @can('administrator_access')
                                <flux:table.cell>
                                    {{ $rate > 0 ? number_format($item->Price / $rate, 2) : '-' }}
                                </flux:table.cell>
                            @endcan
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            @endif
        </flux:table>
    </div>
</flux:modal>
