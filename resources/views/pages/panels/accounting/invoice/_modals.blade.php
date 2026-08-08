@php
    $issuer = $this->issuerName;
@endphp

{{-- Item search command modal --}}
<flux:modal
    name="panels.accounting.invoice.item-search.modal"
    variant="bare"
    class="w-full max-md:my-0 max-md:h-[100dvh] max-md:max-w-none max-md:rounded-none md:my-[12vh] md:max-h-[80vh] md:max-w-[36rem] overflow-y-hidden"
    x-on:close="$wire.set('itemSearch', '', false)"
>
    <flux:command class="border-none shadow-lg inline-flex flex-col max-md:h-[100dvh] md:max-h-[76vh]">
        <flux:command.input
            wire:model.live.debounce.300ms="itemSearch"
            placeholder="{{ __('app.search_item') }}"
            closable
        />

        <div wire:loading.flex wire:target="itemSearch" class="items-center justify-center px-4 py-6">
            <flux:icon name="loader-circle" class="size-5 animate-spin text-zinc-400" />
        </div>

        <flux:command.items wire:loading.remove wire:target="itemSearch">
            @if (strlen(trim($itemSearch)) < 3)
                <div class="px-4 py-6 text-center text-sm text-zinc-500">
                    {{ __('app.search_min_chars', ['count' => 3]) }}
                </div>
            @elseif ($this->itemResults->isEmpty())
                <div class="px-4 py-6 text-center text-sm text-zinc-500">
                    {{ __('app.no_item_found') }}
                </div>
            @else
                @foreach ($this->itemResults as $item)
                    <flux:command.item
                        wire:key="cmd-item-{{ $item['id'] }}"
                        wire:click="selectItem({{ $item['id'] }})"
                        class="cursor-pointer"
                    >
                        <div class="flex items-start gap-3 py-3 min-h-[4.5rem]">
                            @if ($item['thumbnail'])
                                <img
                                    src="data:image/jpeg;base64,{{ base64_encode($item['thumbnail']) }}"
                                    alt=""
                                    class="size-14 shrink-0 rounded-md object-cover shadow-sm"
                                >
                            @else
                                <div class="flex size-14 shrink-0 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="package" class="size-6 text-zinc-400" />
                                </div>
                            @endif
                            <div class="min-w-0 flex-1 space-y-2">
                                <div>
                                    <div class="line-clamp-2 font-medium leading-snug">{{ $item['title'] }}</div>
                                    <div class="mt-0.5 text-xs text-zinc-500">{{ $item['code'] }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs sm:grid-cols-4">
                                    <div>
                                        <span class="text-zinc-500">{{ __('app.stock') }}:</span>
                                        <span class="font-semibold text-teal-600 dark:text-teal-400">{{ number_format($item['stock']) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-500">{{ __('app.last_sale_price') }}:</span>
                                        <span class="font-semibold text-sky-600 dark:text-sky-400">{{ number_format($item['last_sale_price']) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-500">{{ __('app.last_purchase_price') }}:</span>
                                        <span class="font-semibold text-amber-600 dark:text-amber-400">{{ number_format($item['last_purchase_price']) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-500">{{ __('app.site_price') }}:</span>
                                        <span class="font-semibold text-rose-600 dark:text-rose-400">
                                            {{ $item['site_price'] !== null ? number_format($item['site_price']) : '—' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </flux:command.item>
                @endforeach
            @endif
        </flux:command.items>
    </flux:command>
</flux:modal>

{{-- Party balance flyout --}}
<flux:modal name="panels.accounting.invoice.party-balance.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.party_financial_status') }}</flux:heading>
            <flux:text class="mt-2">{{ $this->partyName($this->selectedParty) }}</flux:text>
        </div>

        @if ($partyBalance)
            <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500">{{ __('app.debit') }}</span>
                    <span class="font-semibold tabular-nums text-rose-600">{{ number_format($partyBalance['debtor']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500">{{ __('app.credit') }}</span>
                    <span class="font-semibold tabular-nums text-teal-600">{{ number_format($partyBalance['creditor']) }}</span>
                </div>
                <flux:separator variant="subtle" />
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">{{ __('app.final_balance') }}</flux:heading>
                    <flux:heading size="sm" class="tabular-nums">{{ number_format($partyBalance['final_balance']) }}</flux:heading>
                </div>
            </div>
        @endif
    </div>
</flux:modal>

{{-- Create party flyout --}}
<flux:modal name="panels.accounting.invoice.party-create.modal" class="md:w-96" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_customer') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.create_customer_description') }}</flux:text>
        </div>

        <flux:input wire:model="newPartyName" label="{{ __('app.first_name') }}" required />
        <flux:input wire:model="newPartyLastName" label="{{ __('app.last_name') }}" />
        <flux:input wire:model="newPartyMobile" label="{{ __('app.mobile') }}" required />

        <flux:button type="button" variant="primary" color="orange" class="w-full" icon="save" wire:click="createParty" wire:loading.attr="disabled" wire:target="createParty">
            {{ __('app.save') }}
        </flux:button>
    </div>
</flux:modal>

{{-- Preview flyout --}}
<flux:modal name="panels.accounting.invoice.preview.modal" class="md:w-2/3 lg:w-1/2" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.invoice_preview') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.invoice_preview_description') }}</flux:text>
        </div>

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 space-y-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <flux:text size="sm">{{ __('app.customer') }}</flux:text>
                    <flux:heading size="sm">{{ $this->partyName($this->selectedParty) }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.sale_type') }}</flux:text>
                    <flux:heading size="sm">
                        {{ (int) $form->sale_type_ref === 1 ? __('app.official') : __('app.unofficial') }}
                    </flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.date') }}</flux:text>
                    <flux:heading size="sm">{{ $form->date }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.issuer') }}</flux:text>
                    <flux:heading size="sm">{{ $issuer }}</flux:heading>
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('app.item') }}</flux:table.column>
                    <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('app.fee') }}</flux:table.column>
                    <flux:table.column>{{ __('app.discount') }}</flux:table.column>
                    <flux:table.column>{{ __('app.tax') }}</flux:table.column>
                    <flux:table.column>{{ __('app.line_total') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($items as $index => $row)
                        @php
                            $selectedItem = $row['item_ref'] ? ($this->selectedItems[(int) $row['item_ref']] ?? null) : null;
                            $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
                            $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
                            $discount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
                            $tax = (float) str_replace(',', '', (string) ($row['tax'] ?? 0));
                            $lineTotal = ($qty * $fee) - $discount + $tax;
                        @endphp
                        <flux:table.row wire:key="preview-row-{{ $row['row_id'] ?? $index }}">
                            <flux:table.cell>{{ $selectedItem?->Title ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($qty) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($fee) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($discount) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($tax) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($lineTotal) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="space-y-1 text-sm">
                <div class="flex justify-between"><span>{{ __('app.price') }}</span><span>{{ number_format($this->totals['price']) }}</span></div>
                <div class="flex justify-between"><span>{{ __('app.discount') }}</span><span>{{ number_format($this->totals['discount']) }}</span></div>
                <div class="flex justify-between"><span>{{ __('app.tax') }}</span><span>{{ number_format($this->totals['tax']) }}</span></div>
                <div class="flex justify-between font-bold"><span>{{ __('app.net_amount') }}</span><span>{{ number_format($this->totals['net']) }}</span></div>
            </div>
        </div>
    </div>
</flux:modal>
