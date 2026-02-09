<flux:modal name="panel.shop.sepidar.grouping.item.receipt.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.receipts') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.receipts_description') }}</flux:text>
        </div>
        <flux:table>
            <flux:table.columns class="bg-white dark:bg-zinc-900">
                <flux:table.column>{{ __('app.customer') }}</flux:table.column>
                <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                <flux:table.column>{{ __('app.price') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->buys as $buy)
                    <flux:table.row>
                        <flux:table.cell>
                            {{ $buy->receipt->dl->Title ?? $buy->receipt->DelivererDLRef ?? "" }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ number_format($buy->Fee) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ number_format($buy->Quantity) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ number_format($buy->Price) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($buy->receipt->CreationDate) }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</flux:modal>
