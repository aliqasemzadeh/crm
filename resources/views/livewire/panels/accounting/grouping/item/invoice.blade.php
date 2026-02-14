<flux:modal name="panels.accounting.grouping.item.invoice.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.invoices') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.invoices_description') }}</flux:text>
        </div>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column class="w-1 whitespace-nowrap"></flux:table.column>
                <flux:table.column>{{ __('app.customer') }}</flux:table.column>
                <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                <flux:table.column>{{ __('app.price') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->sales as $sale)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:button size="xs" variant="primary" color="sky" wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { InvoiceId: '{{ $sale->invoice->InvoiceId }}' })">{{ __('app.view') }}</flux:button>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $sale->invoice->CustomerRealName }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ number_format($sale->Fee) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ number_format($sale->Quantity) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($sale->Price) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($sale->invoice->CreationDate) }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</flux:modal>

