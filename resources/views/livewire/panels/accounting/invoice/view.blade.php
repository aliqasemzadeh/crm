<flux:modal name="panels.accounting.invoice.view.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.invoice_items') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.invoice_items_description') }}</flux:text>
        </div>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                <flux:table.column>{{ __('app.price') }}</flux:table.column>
                <flux:table.column>{{ __('app.last_buy_fee') }}</flux:table.column>
                <flux:table.column>{{ __('app.last_buy_price') }}</flux:table.column>
                @can('administrator_access')
                <flux:table.column>{{ __('app.profit') }}</flux:table.column>
                @endcan
            </flux:table.columns>
            @if(isset($invoice))

                @php
                    $profit = 0;
                @endphp

            <flux:table.rows>
                @foreach($invoice->items as $item)
                    @php
                        $cutoff =  \Carbon\Carbon::parse($invoice->CreationDate)->startOfDay();

                        $maxFeeItem = \App\Models\Sepidar\INV\InventoryReceiptItem::query()
                            ->where('ItemRef', $item->ItemRef)
                            ->whereHas('receipt', function ($q) use ($cutoff) {
                                $q->where('Date', '<=', $cutoff);
                            })
                            ->with(['receipt' => function ($q) use ($cutoff) {
                                $q->where('Date', '<=', $cutoff);
                            }])
                            ->orderByDesc('Fee')
                            ->first();
                    @endphp
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:button size="xs" variant="primary" color="sky" wire:click="$dispatch('panels.accounting.grouping.item.invoice.assign-data', { id: '{{ $item->ItemRef}}' })">{{ __('app.invoices') }}</flux:button>
                            <flux:button size="xs" variant="primary" color="green" wire:click="$dispatch('panels.accounting.grouping.item.receipt.assign-data', { id: '{{ $item->ItemRef }}' })">{{ __('app.receipts') }}</flux:button>
                            {{ $item->item->Title }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($item->Quantity) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($item->Fee) }}
                        </flux:table.cell>


                        <flux:table.cell>
                            {{ number_format($item->NetPrice) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($maxFeeItem?->Fee) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($maxFeeItem?->Fee * $item->Quantity) }}
                        </flux:table.cell>

                        @can('administrator_access')
                        <flux:table.cell>
                            {{ number_format($item->NetPrice - $maxFeeItem?->Fee * $item->Quantity) }}
                            @php
                                $profit += $item->NetPrice - $maxFeeItem?->Fee * $item->Quantity;
                            @endphp
                        </flux:table.cell>
                        @endcan
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
                @endif
        </flux:table>
        @can('administrator_access')
        سود نهایی:
        {{ number_format($profit ?? 0) }}
        @endcan
    </div>
</flux:modal>
