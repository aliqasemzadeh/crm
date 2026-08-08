@php
    $issuer = $this->issuerName;
    $invoiceUiPrefix = $invoiceUiPrefix ?? 'panels.accounting.invoice';
    $invoiceRoutePrefix = $invoiceRoutePrefix ?? 'panels.accounting.invoice';
    $partyCreatePermissions = $partyCreatePermissions ?? ['accounting_party_create', 'accounting_invoice_create'];
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
                            <div class="flex items-center gap-1 shrink-0" wire:sort:ignore>
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

                            @canany($partyCreatePermissions)
                                <flux:select.option.create modal="{{ $invoiceUiPrefix }}.party-create.modal" min-length="0">
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
                            <th class="px-3 py-2 text-center font-medium w-28">{{ __('app.options') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700" wire:sort="sortItems">
                        @foreach ($items as $index => $row)
                            @php
                                $rowId = $row['row_id'] ?? ('row-'.$index);
                                $itemRef = $row['item_ref'] ? (int) $row['item_ref'] : null;
                                $selectedItem = $itemRef ? ($this->selectedItems[$itemRef] ?? null) : null;
                                $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
                                $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
                                $discount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
                                $tax = (float) str_replace(',', '', (string) ($row['tax'] ?? 0));
                                $lineTotal = ($qty * $fee) - $discount + $tax;
                            @endphp
                            <tr
                                class="align-top"
                                wire:key="invoice-row-{{ $rowId }}"
                                wire:sort:item="{{ $rowId }}"
                            >
                                <td class="px-3 py-3 text-zinc-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-3 space-y-2 min-w-[240px]" wire:sort:ignore>
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
                                        <button
                                            type="button"
                                            wire:click="openItemSearch({{ $index }})"
                                            class="flex w-full items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-start text-sm text-zinc-500 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                                        >
                                            <flux:icon name="magnifying-glass" variant="micro" class="text-zinc-400" />
                                            <span>{{ __('app.search_item') }}</span>
                                        </button>
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
                                <td class="px-3 py-3" wire:sort:ignore>
                                    <flux:input wire:model.blur="items.{{ $index }}.quantity" type="number" step="any" min="0" size="sm" />
                                </td>
                                <td class="px-3 py-3" wire:sort:ignore>
                                    <flux:input
                                        wire:model.blur="items.{{ $index }}.fee"
                                        size="sm"
                                        mask:dynamic="$money($input, '.', ',', 0)"
                                    />
                                </td>
                                <td class="px-3 py-3" wire:sort:ignore>
                                    <flux:input
                                        wire:model.blur="items.{{ $index }}.discount"
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
                                        <button type="button" wire:sort:handle class="cursor-grab p-1 text-zinc-400 hover:text-zinc-600">
                                            <flux:icon name="grip-vertical" variant="micro" />
                                        </button>
                                        <div class="flex items-center gap-1" wire:sort:ignore>
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
                <flux:button type="button" variant="ghost" href="{{ route($invoiceRoutePrefix.'.index') }}" wire:navigate icon="arrow-right">
                    {{ __('app.back') }}
                </flux:button>
                <flux:button type="button" variant="primary" color="sky" icon="eye" wire:click="openPreview">
                    {{ __('app.preview') }}
                </flux:button>
                <flux:button type="submit" variant="primary" color="orange" class="w-full sm:w-auto" icon="save" wire:loading.attr="disabled" wire:target="save">
                    {{ __('app.save') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>
