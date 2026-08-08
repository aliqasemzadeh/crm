@php
    $issuer = $this->issuerName;
@endphp

<div class="w-full">
    <div class="rounded-xl border border-zinc-300 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-4 sm:px-6 dark:border-zinc-700 dark:bg-zinc-800/60">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <flux:heading size="xl">{{ $heading }}</flux:heading>
                    <flux:text class="mt-1">{{ $subheading }}</flux:text>
                </div>
                <div class="text-sm space-y-1 md:text-left">
                    <div class="flex gap-2 justify-between md:justify-end">
                        <span class="text-zinc-500">{{ __('app.issuer') }}:</span>
                        <span class="font-medium">{{ $issuer }}</span>
                    </div>
                    @isset($invoiceNumber)
                        <div class="flex gap-2 justify-between md:justify-end">
                            <span class="text-zinc-500">{{ __('app.invoice_number') }}:</span>
                            <span class="font-medium">{{ $invoiceNumber }}</span>
                        </div>
                    @endisset
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-2 lg:col-span-1">
                    <flux:label>{{ __('app.customer') }}</flux:label>

                    @if ($this->selectedParty)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <div class="min-w-0">
                                <flux:text>{{ $this->partyName($this->selectedParty) }}</flux:text>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <flux:tooltip content="{{ __('app.party_financial_status') }}">
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        color="sky"
                                        icon="wallet"
                                        wire:click="showPartyBalance({{ $this->selectedParty->PartyId }})"
                                    />
                                </flux:tooltip>
                                <flux:button type="button" size="xs" variant="ghost" color="red" icon="x-mark" wire:click="clearParty" />
                            </div>
                        </div>
                    @else
                        <flux:select variant="combobox" :filter="false" wire:model.live="form.customer_party_ref" placeholder="{{ __('app.search_customer') }}">
                            <x-slot name="input">
                                <flux:select.input wire:model.live.debounce.400ms="partySearch" placeholder="{{ __('app.search_customer') }}" />
                            </x-slot>

                            @foreach ($this->partyResults as $party)
                                <flux:select.option value="{{ $party->PartyId }}" wire:key="party-{{ $party->PartyId }}">
                                    {{ $this->partyName($party) }}
                                </flux:select.option>
                            @endforeach

                            @canany(['accounting_party_create', 'accounting_invoice_create'])
                                <flux:select.option.create modal="panels.accounting.invoice.party-create.modal" min-length="0">
                                    {{ __('app.create_customer') }}
                                </flux:select.option.create>
                            @endcanany
                        </flux:select>
                    @endif

                    @error('form.customer_party_ref')
                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:radio.group wire:model.live="form.sale_type_ref" label="{{ __('app.sale_type') }}" variant="segmented">
                        <flux:radio value="1" label="{{ __('app.official') }}" />
                        <flux:radio value="2" label="{{ __('app.unofficial') }}" />
                    </flux:radio.group>
                </div>

                <x-date-select wire:model="form.date" :label="__('app.date')" :required="true" />

                <flux:input wire:model="form.description" label="{{ __('app.description') }}" />
            </div>

            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <tr>
                            <th class="px-3 py-2 text-right font-medium w-10">#</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('app.item') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-24">{{ __('app.quantity') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-36">{{ __('app.fee') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-28">{{ __('app.discount') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-28">{{ __('app.tax') }}</th>
                            <th class="px-3 py-2 text-right font-medium w-32">{{ __('app.line_total') }}</th>
                            <th class="px-3 py-2 text-center font-medium w-24">{{ __('app.options') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($items as $index => $row)
                            @php
                                $itemRef = $row['item_ref'] ? (int) $row['item_ref'] : null;
                                $selectedItem = $itemRef ? ($this->selectedItems[$itemRef] ?? null) : null;
                                $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
                                $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
                                $discount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
                                $tax = (float) str_replace(',', '', (string) ($row['tax'] ?? 0));
                                $lineTotal = ($qty * $fee) - $discount + $tax;
                            @endphp
                            <tr class="align-top" wire:key="invoice-row-{{ $index }}">
                                <td class="px-3 py-3 text-zinc-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-3 space-y-2 min-w-[240px]">
                                    @if ($selectedItem)
                                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-2 py-1.5 dark:border-zinc-700">
                                            @if ($selectedItem->image?->Thumbnail)
                                                <img
                                                    src="data:image/jpeg;base64,{{ base64_encode($selectedItem->image->Thumbnail) }}"
                                                    alt=""
                                                    class="size-9 rounded object-cover shadow-sm"
                                                >
                                            @else
                                                <div class="flex size-9 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                                    <flux:icon name="package" variant="micro" class="text-zinc-400" />
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate font-medium">{{ $selectedItem->Title }}</div>
                                                <div class="truncate text-xs text-zinc-500">{{ $selectedItem->Code }}</div>
                                            </div>
                                            <flux:button type="button" size="xs" variant="ghost" wire:click="clearItem({{ $index }})">
                                                {{ __('app.change') }}
                                            </flux:button>
                                        </div>
                                    @else
                                        <flux:input
                                            as="button"
                                            type="button"
                                            icon="magnifying-glass"
                                            placeholder="{{ __('app.search_item') }}"
                                            wire:click="openItemSearch({{ $index }})"
                                            class="w-full cursor-pointer text-start"
                                        />
                                    @endif

                                    <flux:input
                                        wire:model.blur="items.{{ $index }}.description"
                                        placeholder="{{ __('app.description') }}"
                                        size="sm"
                                    />

                                    @error("items.$index.item_ref")
                                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                                    @enderror
                                </td>
                                <td class="px-3 py-3">
                                    <flux:input wire:model.live.blur="items.{{ $index }}.quantity" type="number" step="any" min="0" size="sm" />
                                </td>
                                <td class="px-3 py-3">
                                    <flux:input
                                        wire:model.live.blur="items.{{ $index }}.fee"
                                        size="sm"
                                        mask:dynamic="$money($input, '.', ',', 0)"
                                    />
                                </td>
                                <td class="px-3 py-3">
                                    <flux:input
                                        wire:model.live.blur="items.{{ $index }}.discount"
                                        size="sm"
                                        mask:dynamic="$money($input, '.', ',', 0)"
                                    />
                                </td>
                                <td class="px-3 py-3">
                                    <div class="pt-2 tabular-nums text-zinc-600 dark:text-zinc-300">{{ number_format($tax) }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="pt-2 font-semibold tabular-nums">{{ number_format($lineTotal) }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-center gap-1 pt-1">
                                        <flux:tooltip content="{{ __('app.remove_row') }}">
                                            <flux:button
                                                type="button"
                                                size="xs"
                                                variant="primary"
                                                color="red"
                                                icon="minus"
                                                icon:variant="outline"
                                                wire:click="removeRow({{ $index }})"
                                                :disabled="count($items) <= 1"
                                            />
                                        </flux:tooltip>
                                        <flux:tooltip content="{{ __('app.insert_row') }}">
                                            <flux:button
                                                type="button"
                                                size="xs"
                                                variant="primary"
                                                color="teal"
                                                icon="plus"
                                                icon:variant="outline"
                                                wire:click="insertRowAfter({{ $index }})"
                                            />
                                        </flux:tooltip>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:flex-row md:items-end md:justify-between">
                <div class="ms-auto w-full max-w-sm space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.price') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($this->totals['price']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.discount') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($this->totals['discount']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.tax') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($this->totals['tax']) }}</span>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">{{ __('app.net_amount') }}</flux:heading>
                        <flux:heading size="lg" class="tabular-nums">{{ number_format($this->totals['net']) }}</flux:heading>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:button type="button" variant="ghost" href="{{ route('panels.accounting.invoice.index') }}" wire:navigate icon="arrow-right">
                    {{ __('app.back') }}
                </flux:button>
                <flux:button type="button" variant="primary" color="sky" icon="eye" wire:click="openPreview">
                    {{ __('app.preview') }}
                </flux:button>
                <flux:button type="submit" variant="primary" color="orange" class="w-full sm:w-auto" icon="save" wire:loading.attr="disabled">
                    {{ __('app.save') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>

{{-- Item search command modal --}}
<flux:modal
    name="panels.accounting.invoice.item-search.modal"
    variant="bare"
    class="w-full max-w-[36rem] my-[10vh] max-h-screen overflow-y-hidden"
    x-on:close="$wire.set('itemSearch', '', false)"
>
    <flux:command class="border-none shadow-lg inline-flex flex-col max-h-[76vh]">
        <flux:command.input
            wire:model.live.debounce.300ms="itemSearch"
            placeholder="{{ __('app.search_item') }}"
            closable
        />

        <flux:command.items>
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
                        <div class="flex items-start gap-3 py-1">
                            @if ($item['thumbnail'])
                                <img
                                    src="data:image/jpeg;base64,{{ base64_encode($item['thumbnail']) }}"
                                    alt=""
                                    class="size-12 shrink-0 rounded-md object-cover shadow-sm"
                                >
                            @else
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="package" class="size-5 text-zinc-400" />
                                </div>
                            @endif
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="truncate font-medium">{{ $item['title'] }}</span>
                                    <span class="text-xs text-zinc-500">{{ $item['code'] }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-x-2 text-xs">
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
    <form wire:submit="createParty" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.create_customer') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.create_customer_description') }}</flux:text>
        </div>

        <flux:input wire:model="newPartyName" label="{{ __('app.first_name') }}" required />
        <flux:input wire:model="newPartyLastName" label="{{ __('app.last_name') }}" />
        <flux:input wire:model="newPartyMobile" label="{{ __('app.mobile') }}" required />

        <flux:button type="submit" variant="primary" color="orange" class="w-full" icon="save">
            {{ __('app.save') }}
        </flux:button>
    </form>
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
                        <flux:table.row wire:key="preview-row-{{ $index }}">
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
