@php
    $profit = 0;
    $profitUsdt = 0;
    $totalSellUsdt = 0;
@endphp
<flux:modal name="panels.accounting.invoice.view.modal" class="md:w-2/3 xl:w-3/4" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.invoice_items') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.invoice_items_description') }}</flux:text>
        </div>

        @if(isset($invoice))
            @php
                $partyName = $invoice->customer
                    ? trim(implode(' ', array_filter([
                        trim((string) ($invoice->customer->Name ?? '')),
                        trim((string) ($invoice->customer->LastName ?? '')),
                    ], static fn (string $part): bool => $part !== '')))
                    : (string) ($invoice->CustomerRealName ?? '-');
                if ($partyName === '') {
                    $partyName = (string) ($invoice->CustomerRealName ?? '-');
                }
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="flex flex-col">
                    <flux:text size="sm">{{ __('app.number') }}</flux:text>
                    <flux:heading size="sm">{{ $invoice->Number }}</flux:heading>
                </div>
                <div class="flex flex-col">
                    <flux:text size="sm">{{ __('app.date') }}</flux:text>
                    <flux:heading size="sm">
                        {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('Y/m/d') : '-' }}
                    </flux:heading>
                </div>
                <div class="flex flex-col">
                    <flux:text size="sm">{{ __('app.customer') }}</flux:text>
                    <flux:heading size="sm">{{ $partyName }}</flux:heading>
                </div>
                <div class="flex flex-col">
                    <flux:text size="sm">{{ __('app.category') }}</flux:text>
                    <flux:heading size="sm">
                        {{ (int) $invoice->SaleTypeRef === 1 ? __('app.official') : __('app.unofficial') }}
                    </flux:heading>
                </div>
                <div class="flex flex-col">
                    <flux:text size="sm">{{ __('app.creator') }}</flux:text>
                    <flux:heading size="sm">{{ $invoice->creator?->Name ?? '-' }}</flux:heading>
                </div>
                <div class="flex flex-col">
                    <flux:text size="sm">{{ __('app.last_modifier') }}</flux:text>
                    <flux:heading size="sm">{{ $invoice->modifier?->Name ?? '-' }}</flux:heading>
                </div>
                <div class="flex flex-col">
                    <flux:text size="sm">{{ __('app.last_modification_date') }}</flux:text>
                    <flux:heading size="sm">
                        {{ $invoice->LastModificationDate ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->LastModificationDate)->format('Y/m/d H:i') : '-' }}
                    </flux:heading>
                </div>
            </div>
        @endif

        <div class="flex justify-end">
            <flux:button size="sm" variant="primary" color="sky"
                         x-on:click.prevent="if (confirm('{{ __('app.send_invoice_to_customer_confirm') }}')) { $wire.sendToCustomer() }">
                {{ __('app.send_invoice_to_customer') }}
            </flux:button>
        </div>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns class="bg-white dark:bg-zinc-900">
                    <flux:table.column>{{ __('app.name') }}</flux:table.column>
                    <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                    <flux:table.column>{{ __('app.discount') }}</flux:table.column>
                    <flux:table.column>{{ __('app.tax') }}</flux:table.column>
                    <flux:table.column>{{ __('app.line_total') }}</flux:table.column>
                    <flux:table.column>{{ __('app.last_buy_fee') }}</flux:table.column>
                    <flux:table.column>{{ __('app.last_buy_price') }}</flux:table.column>
                    @can('accounting_profit_index')
                        <flux:table.column>{{ __('app.buy_price_usdt') }}</flux:table.column>
                        <flux:table.column>{{ __('app.sell_price_usdt') }}</flux:table.column>
                        <flux:table.column>{{ __('app.profit') }}</flux:table.column>
                        <flux:table.column>{{ __('app.profit_usdt') }}</flux:table.column>
                    @endcan
                </flux:table.columns>
                @if(isset($invoice))
                    @php
                        $invoiceRate = \App\Models\CurrencyRate::getRate($invoice->Date) / 10;
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
                                    {{ number_format($item->Discount) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ number_format($item->Tax) }}
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

                                @can('accounting_profit_index')
                                    @php
                                        $buyRate = \App\Models\CurrencyRate::getRate($maxFeeItem?->receipt?->Date) / 10;
                                        $buyPriceUsdt = $buyRate > 0 ? ($maxFeeItem?->Fee * $item->Quantity) / $buyRate : 0;
                                        $sellPriceUsdt = $invoiceRate > 0 ? $item->NetPrice / $invoiceRate : 0;
                                        $itemProfit = $item->NetPrice - $maxFeeItem?->Fee * $item->Quantity;
                                        $itemProfitUsdt = $sellPriceUsdt - $buyPriceUsdt;

                                        $profit += $itemProfit;
                                        $profitUsdt += $itemProfitUsdt;
                                        $totalSellUsdt += $sellPriceUsdt;
                                    @endphp
                                    <flux:table.cell>
                                        {{ number_format($buyPriceUsdt, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ number_format($sellPriceUsdt, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ number_format($itemProfit) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ number_format($itemProfitUsdt, 2) }}
                                    </flux:table.cell>
                                @endcan
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                @endif
            </flux:table>
        </div>

        @if(isset($invoice))
            <div class="ms-auto w-full max-w-sm space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('app.price') }}</span>
                    <span class="font-medium tabular-nums">{{ number_format($invoice->Price) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('app.discount') }}</span>
                    <span class="font-medium tabular-nums">{{ number_format($invoice->Discount) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('app.tax') }}</span>
                    <span class="font-medium tabular-nums">{{ number_format($invoice->Tax) }}</span>
                </div>
                <flux:separator variant="subtle" />
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">{{ __('app.net_amount') }}</flux:heading>
                    <flux:heading size="lg" class="tabular-nums">{{ number_format($invoice->NetPrice) }}</flux:heading>
                </div>
            </div>
        @endif

        @can('accounting_profit_index')
            <div class="flex flex-col gap-2">
                <flux:heading size="lg">{{ __('app.total_profit_usdt') }}: {{ number_format($profitUsdt, 2) }} {{ __('app.usdt') }}</flux:heading>
                <flux:heading size="lg">{{ __('app.total_sell_usdt') }}: {{ number_format($totalSellUsdt, 2) }} {{ __('app.usdt') }}</flux:heading>
                <flux:heading size="lg">{{ __('app.total_profit') }}: {{ number_format($profit ?? 0) }} {{ __('app.rial') }}</flux:heading>
            </div>
        @endcan
    </div>
</flux:modal>
