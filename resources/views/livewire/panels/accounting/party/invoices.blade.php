<flux:modal name="panel.shop.sepidar.party.invoices.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.invoices') }}:  {{ $part->Name ?? "" }} {{ $part->LastName ?? "" }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.invoices_description') }}</flux:text>
        </div>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.actions') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
                <flux:table.column>{{ __('app.price') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @php
                    $sum = 0;
                @endphp
                @foreach($this->invoices as $invoice)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:button size="xs" icon="eye" variant="primary" color="sky" wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { id: '{{ $invoice->id }}' })">{{ __('app.view') }}</flux:button>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($invoice->CreationDate) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($invoice->Price) }}
                            @php
                                $sum += $invoice->Price;
                            @endphp
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
            جمع فروش به مشتری:
            {{ number_format($sum ?? 0) }}
    </div>
</flux:modal>

